<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ config('app.name') }}</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="{{ url('/') }}">{{ config('app.name') }}</a>

    <div class="ms-auto d-flex gap-2">
      @auth
        <a class="btn btn-outline-light btn-sm" href="{{ route('dashboard') }}">Dashboard</a>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button class="btn btn-danger btn-sm" type="submit">Salir</button>
        </form>
      @else
        <a class="btn btn-outline-light btn-sm" href="{{ route('login') }}">Login</a>
        <a class="btn btn-warning btn-sm" href="{{ route('register') }}">Registro</a>
      @endauth
    </div>
  </div>
</nav>

<main class="container py-5">
  @if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  {{ $slot ?? '' }}
  @yield('content')
</main>

</body>
</html>