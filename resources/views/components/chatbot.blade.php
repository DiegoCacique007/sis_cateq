@if(auth()->check() && auth()->user()->isApprovedForAccess())
    @vite(['resources/js/chatbot.js'])

    <div class="cateq-chat" data-chatbot data-endpoint="{{ route('chatbot.message', [], false) }}" data-csrf="{{ csrf_token() }}">
        <button type="button" class="cateq-chat-launch" aria-expanded="false" aria-controls="cateq-chat-panel" aria-label="Abrir asistente" title="Asistente SIS_CATEQ">
            <img src="{{ asset('images/chatbot/robot.png') }}" alt="Asistente SIS_CATEQ" class="cateq-chat-logo">
        </button>

        <section id="cateq-chat-panel" class="cateq-chat-panel" aria-label="Asistente de SIS_CATEQ" hidden>
            <header>
                <div><strong>Asistente</strong><small>Consultas y orientación</small></div>
                <button type="button" data-close aria-label="Cerrar asistente">×</button>
            </header>

            <div class="cateq-chat-log" role="log" aria-live="polite" aria-relevant="additions" tabindex="0">
                <div class="cateq-chat-message">Hola. Puedes preguntar «¿Qué puedes hacer?». Las consultas respetan tus permisos y el periodo seleccionado.</div>
            </div>

            <p data-progress role="status" hidden>Procesando…</p>

            <form class="cateq-chat-form">
                <label for="cateq-chat-input">Tu consulta</label>
                <div><input id="cateq-chat-input" name="message" type="text" required minlength="2" maxlength="2000" autocomplete="off" placeholder="Escribe una consulta…"><button type="submit">Enviar</button></div>
                <small>Solo lectura y orientación. No realiza cambios.</small>
            </form>
        </section>
    </div>
@endif
