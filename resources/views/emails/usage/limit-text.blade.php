Hi {{ $name }},

You have used {{ number_format($used) }} of {{ number_format($limit) }} requests this month ({{ $percentage }}%).

{{ $atLimit ? 'Requests will be available again' : 'Your monthly allowance resets' }} on {{ $resetsAt }}.

View your dashboard: {{ $dashboardUrl }}
