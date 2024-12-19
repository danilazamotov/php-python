<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Python IDE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/dracula.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/hint/show-hint.css" rel="stylesheet">
    <style>
        .CodeMirror {
            height: 400px;
            border: 1px solid #ddd;
            font-family: 'Consolas', monospace;
            font-size: 14px;
            line-height: 1.5;
            padding-left: 10px;
        }
        .CodeMirror-lines {
            padding-left: 0;
        }
        .CodeMirror-linenumbers {
            padding: 0 8px;
        }
        .CodeMirror-linenumber {
            padding: 0;
            min-width: 25px;
            text-align: right;
            color: #999;
            white-space: nowrap;
            box-sizing: content-box;
        }
        .CodeMirror-gutters {
            border-right: 1px solid #ddd;
            background-color: #f7f7f7;
            white-space: nowrap;
            padding-right: 5px;
            min-width: 35px;
        }
        .CodeMirror-sizer {
            margin-left: 45px !important;
        }
        #output {
            height: 200px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 10px;
            overflow-y: auto;
            font-family: 'Consolas', monospace;
            font-size: 14px;
        }
        .dark-theme {
            background-color: #2d2d2d;
            color: #fff;
        }
        .dark-theme #output {
            background-color: #1e1e1e;
            color: #fff;
            border-color: #444;
        }
        .dark-theme .CodeMirror-gutters {
            background-color: #2d2d2d;
            border-color: #444;
        }
        .dark-theme .CodeMirror-linenumber {
            color: #888;
        }
        .tab-content {
            border: 1px solid #ddd;
            border-top: none;
            padding: 15px;
        }
        .nav-tabs {
            margin-bottom: 0;
        }
        .tab-pane {
            position: relative;
        }
        .close-tab {
            margin-left: 10px;
            cursor: pointer;
        }
        #fileExplorer {
            background-color: #f8f9fa;
        }
        .dark-theme #fileExplorer {
            background-color: #1e1e1e;
            border-color: #444;
        }
        .file-item {
            display: flex;
            align-items: center;
            padding: 8px;
            border-bottom: 1px solid #ddd;
            background: #fff;
        }
        .file-item:hover {
            background-color: #f8f9fa;
        }
        .file-name {
            flex-grow: 1;
            cursor: pointer;
            margin-right: 10px;
            display: flex;
            align-items: center;
        }
        .file-name i {
            margin-right: 8px;
            color: #0d6efd;
        }
        .file-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .file-actions button {
            padding: 4px 8px;
            font-size: 14px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .file-actions button i {
            margin-right: 4px;
        }
        .file-actions .rename-btn {
            background-color: #0d6efd;
            color: white;
            border: none;
        }
        .file-actions .download-btn {
            background-color: #198754;
            color: white;
            border: none;
        }
        .file-actions .delete-btn {
            background-color: #dc3545;
            color: white;
            border: none;
        }
        .rename-input {
            flex-grow: 1;
            padding: 4px 8px;
            border: 1px solid #0d6efd;
            border-radius: 4px;
            margin-right: 8px;
        }
        .dark-theme .file-item {
            background: #2d2d2d;
            border-color: #444;
        }
        .dark-theme .file-item:hover {
            background: #383838;
        }
        .dark-theme .file-name i {
            color: #58a6ff;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row mb-3">
            <div class="col">
                <h1 class="float-start">Python IDE</h1>
                <div class="float-end">
                    <button id="themeToggle" class="btn btn-secondary">Сменить тему</button>
                    <button id="formatCode" class="btn btn-info">Форматировать код</button>
                    <button id="newFile" class="btn btn-primary">Новый файл</button>
                    <input type="file" id="openFile" accept=".py" class="d-none">
                    <button id="openFileBtn" class="btn btn-success">Открыть файл</button>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-9">
                <!-- Tabs -->
                <ul class="nav nav-tabs" id="editorTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab1" data-bs-toggle="tab" href="#content1" role="tab">main.py</a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="editorTabContent">
                    <div class="tab-pane fade show active" id="content1" role="tabpanel">
                        <textarea id="code" placeholder="Напишите свой Python код здесь...">print("Привет, мир!")</textarea>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col">
                        <button id="runBtn" class="btn btn-primary">Запустить</button>
                        <button id="saveBtn" class="btn btn-success">Сохранить</button>
                    </div>
                </div>
            </div>

            <div class="col-3">
                <h4>Файлы</h4>
                <div id="fileExplorer" class="border p-2" style="height: 400px; overflow-y: auto;">
                    <!-- File list will be populated here -->
                </div>
                <div class="mt-2">
                    <button id="downloadSelected" class="btn btn-secondary btn-sm" style="display: none;">
                        Скачать выбранные
                    </button>
                    <button id="deleteSelected" class="btn btn-danger btn-sm" style="display: none;">
                        Удалить выбранные
                    </button>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12">
                <h4>Вывод:</h4>
                <div id="output"></div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/python/python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/edit/closebrackets.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/edit/matchbrackets.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/hint/show-hint.min.js"></script>
    <script src="assets/js/python-hint.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
