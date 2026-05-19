</main><!-- /page-content -->
    <footer class="page-footer">
        <span>&copy; <?= date('Y') ?> <?= SITE_FULL ?> &mdash; <?= BARANGAY_NAME ?>, <?= BARANGAY_CITY ?></span>
    </footer>
</div><!-- /main-wrapper -->

<!-- CONFIRM MODAL -->
<div class="modal-backdrop" id="confirmModal" style="display:none;">
    <div class="modal">
        <div class="modal-header">
            <h3 id="confirmTitle">Confirm Action</h3>
            <button class="modal-close" data-close="confirmModal">&times;</button>
        </div>
        <div class="modal-body">
            <p id="confirmMessage">Are you sure you want to proceed?</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" data-close="confirmModal">Cancel</button>
            <button class="btn btn-danger" id="confirmBtn">Confirm</button>
        </div>
    </div>
</div>

<!-- TOAST CONTAINER -->
<div class="toast-container" id="toastContainer"></div>

<script src="<?= isset($basePath) ? $basePath : '' ?>js/script.js"></script>
</body>
</html>