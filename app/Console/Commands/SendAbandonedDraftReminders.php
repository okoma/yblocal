<?php

namespace App\Console\Commands;

use App\Models\GuestBusinessDraft;
use App\Mail\AbandonedBusinessDraft;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendAbandonedDraftReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drafts:send-reminders
                          {--dry-run : Show what would be sent without actually sending}
                          {--force : Send reminders even if already sent today}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email reminders to guests who abandoned business listing forms';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('🔍 Checking for abandoned business drafts...');

        // First reminder: 24 hours of inactivity, no reminder sent yet
        $firstReminders = GuestBusinessDraft::needingReminder(24)->get();
        
        // Second reminder: 48 hours of inactivity, only 1 reminder sent
        $secondReminders = GuestBusinessDraft::needingSecondReminder()->get();

        $totalToSend = $firstReminders->count() + $secondReminders->count();

        if ($totalToSend === 0) {
            $this->info('✅ No abandoned drafts found needing reminders.');
            return 0;
        }

        $this->info("📧 Found {$totalToSend} draft(s) needing reminders:");
        $this->info("   • First reminder: {$firstReminders->count()}");
        $this->info("   • Second reminder: {$secondReminders->count()}");

        if ($dryRun) {
            $this->warn('🔸 DRY RUN MODE - No emails will be sent');
            $this->newLine();
        }

        $sent = 0;
        $failed = 0;

        // Send first reminders (24 hours)
        foreach ($firstReminders as $draft) {
            $this->processReminder($draft, 1, $dryRun, $sent, $failed);
        }

        // Send second reminders (48 hours)
        foreach ($secondReminders as $draft) {
            $this->processReminder($draft, 2, $dryRun, $sent, $failed);
        }

        $this->newLine();
        $this->info("📊 Summary:");
        $this->info("   ✅ Successfully sent: {$sent}");
        if ($failed > 0) {
            $this->error("   ❌ Failed: {$failed}");
        }

        return $failed > 0 ? 1 : 0;
    }

    /**
     * Process a single reminder
     */
    protected function processReminder(
        GuestBusinessDraft $draft, 
        int $reminderNumber, 
        bool $dryRun, 
        int &$sent, 
        int &$failed
    ): void {
        $businessName = $draft->getBusinessName() ?? 'Unknown Business';
        $email = $draft->guest_email;

        $this->line("📤 [{$reminderNumber}] {$businessName} → {$email}");

        if ($dryRun) {
            $this->line("   Would send reminder #{$reminderNumber} to {$email}");
            $sent++;
            return;
        }

        try {
            Mail::to($email)->send(new AbandonedBusinessDraft($draft, $reminderNumber));
            
            // Mark as sent
            $draft->markReminderSent();
            
            $this->info("   ✅ Sent successfully");
            $sent++;

            // Log the event
            Log::info("Abandoned draft reminder sent", [
                'draft_id' => $draft->id,
                'email' => $email,
                'reminder_number' => $reminderNumber,
                'business_name' => $businessName,
                'completion' => $draft->getCompletionPercentage() . '%',
            ]);

        } catch (\Exception $e) {
            $this->error("   ❌ Failed: " . $e->getMessage());
            $failed++;

            Log::error("Failed to send abandoned draft reminder", [
                'draft_id' => $draft->id,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
