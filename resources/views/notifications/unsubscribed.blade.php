<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unsubscribed</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  </head>
  <body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="bg-white shadow-md rounded-lg p-8 max-w-xl w-full text-center">
      <h1 class="text-2xl font-semibold mb-4">You're unsubscribed</h1>
      <p class="text-gray-700 mb-4">We've updated your preferences for <strong>{{ $topic ?? 'notifications' }}</strong> for <strong>{{ $email }}</strong>.</p>
      <p class="text-sm text-gray-500">If this was a mistake, you can manage your notification preferences in your account settings after signing in.</p>
      <div class="mt-6">
        <a href="/" class="inline-block bg-blue-600 text-white px-4 py-2 rounded">Return to site</a>
      </div>
    </div>
  </body>
</html>
