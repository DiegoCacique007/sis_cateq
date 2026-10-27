@vite(['resources/js/alerts.js'])
<script type="application/json" id="app-alert-flash">{!! json_encode(['success' => session('success'), 'status' => session('status'), 'error' => session('error'), 'warning' => session('warning'), 'info' => session('info'), 'errors' => $errors->messages()], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
