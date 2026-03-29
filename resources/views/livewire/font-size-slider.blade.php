<div>
    <hr style="margin: -5px; margin-top: 5px; border-top: 1px solid #e5e5e5">
    <div style="margin: 10px">

        {{ $this->form }}
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.documentElement.style.fontSize = '{{ auth()->user()?->font_size ?? 16 }}px';

            window.addEventListener('font-size-updated', event => {
                document.documentElement.style.fontSize = event.detail.size + 'px';
            });
        });
    </script>
</div>


