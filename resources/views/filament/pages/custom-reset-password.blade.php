<div class="w-full h-full">
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var url = document.getElementById('bg-image').getAttribute('data-url');
            var bgImageDiv = document.createElement('div');
            bgImageDiv.classList.add('bg-image');

            var img = document.createElement('img');
            img.src = url;
            img.alt = '';

            bgImageDiv.appendChild(img);
            document.body.prepend(bgImageDiv);
        });

        document.addEventListener('DOMContentLoaded', function() {
            var elements = document.querySelectorAll('.fls-display-on');
            elements.forEach(function(element) {
                element.style.right = '0';
            });
        });
    </script>
    <div class="login-container">
        <div class="form-container">
            <div class="my-0">
                {{ $this->content }}
            </div>
        </div>
    </div>
</div>
