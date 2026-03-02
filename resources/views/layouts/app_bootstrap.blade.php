<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Sis_Cateq')</title>

  {{-- Bootstrap --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="{{ route('dashboard') }}">Sis_Cateq</a>

    <div class="ms-auto d-flex gap-2 align-items-center">
      @auth
        <span class="text-white-50 small">
          {{ auth()->user()->name }} ({{ auth()->user()->role }})
        </span>

        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button class="btn btn-outline-light btn-sm" type="submit">Salir</button>
        </form>
      @endauth
    </div>
  </div>
</nav>

<main class="container py-4">
  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>