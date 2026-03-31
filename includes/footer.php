</div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function() {
            // ดึงชื่อไฟล์ปัจจุบันมาทำ Active Menu
            const currentLocation = location.pathname.split("/").slice(-1)[0];
            $('.nav-link').each(function() {
                if ($(this).attr('href') === currentLocation) {
                    $(this).addClass('active bg-primary text-white');
                    $(this).find('i').addClass('text-white');
                }
            });
        });
    </script>
</body>
</html>