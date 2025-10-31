</main>

<footer class="text-center py-3 mt-auto bg-light border-top">
    <small>© <?= date('Y') ?> Dinas Pemadam Kebakaran — Sistem Penjadwalan Otomatis</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/lucide-icons@latest"></script>
<script>
    lucide.createIcons();
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('menu-toggle');
    if (toggle) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });
    }
</script>
</body>

</html>