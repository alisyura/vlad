<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-lg-9 col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 fw-bold">Управление медиафайлами</h5>
                    <form id="adminUploadForm" class="d-flex gap-2">
                        <input type="file" id="adminFileInput" class="form-control form-control-sm" accept="image/*" required>
                        <input type="text" id="adminAltInput" class="form-control form-control-sm" placeholder="Alt-текст" required>
                        <button type="submit" class="btn btn-primary btn-sm">Загрузить</button>
                    </form>
                </div>
                <div class="card-body">
                    <div id="adminMediaGallery" class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3">
                        </div>
                    
                    <div id="adminPagination" class="d-flex justify-content-center mt-4"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-4">
            <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
                <div class="card-header bg-light fw-bold">Детали файла</div>
                <div id="fileDetailsPanel" class="card-body">
                    <div id="detailsPlaceholder" class="text-center text-muted py-5">
                        <i class="bi bi-image" style="font-size: 2rem;"></i>
                        <p class="mt-2">Выберите изображение, чтобы увидеть подробности</p>
                    </div>
                    
                    <div id="detailsContent" style="display: none;">
                        <img id="detailPreview" src="" class="img-fluid rounded mb-3 shadow-sm" alt="">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Путь к файлу:</label>
                            <input type="text" id="detailPath" class="form-control form-control-sm bg-light" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Alt-текст (SEO):</label>
                            <input type="text" id="detailAlt" class="form-control form-control-sm">
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button id="saveAltBtn" class="btn btn-success btn-sm">Сохранить изменения</button>
                            <hr>
                            <button id="deleteMediaBtn" class="btn btn-outline-danger btn-sm">Удалить файл</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>