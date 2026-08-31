<?php

session_start();

// if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'ADMIN') {
//     header("Location: index.php");
//     exit;
// }

include "./include/header.php";


?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

    body {
        font-family: 'Inter', sans-serif;
        background: linear-gradient(135deg, #e0f2fe, #f8fafc);
    }

    .glass-card {
        background: rgba(255, 255, 255, 0.80);
        border: 1px solid rgba(255, 255, 255, 0.9);
        border-radius: 24px;
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        box-shadow: 0 8px 30px rgba(15, 23, 42, .06);
    }

    .upload-dropzone {
        border: 2px dashed #cbd5e1;
        background: #f8fafc;
        transition: all .25s ease;
    }

    .upload-dropzone:hover,
    .upload-dropzone.dragover {
        border-color: #10b981;
        background: #f0fdf4;
    }

    .upload-dropzone.has-file {
        border-color: #10b981;
        background: #ecfdf5;
    }

    .btn-modern {
        background: #0f172a;
        color: #fff;
        border: 0;
        transition: all .25s ease;
    }

    .btn-modern:hover {
        background: #10b981;
        color: #fff;
        transform: translateY(-1px);
    }

    .btn-modern:disabled {
        opacity: .65;
        cursor: not-allowed;
        transform: none;
    }

    .footer-rule-card {
        background: rgba(255, 255, 255, .75);
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 20px;
        transition: all .2s ease;
    }

    .footer-rule-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(15, 23, 42, .05);
    }

    .progress-track {
        width: 100%;
        height: 7px;
        background: #e2e8f0;
        border-radius: 20px;
        overflow: hidden;
    }

    .progress-value {
        height: 100%;
        width: 0%;
        background: #10b981;
        border-radius: 20px;
        transition: width .4s ease;
    }

    .file-name-box {
        margin-top: 15px;
        padding: 12px 14px;
        background: #ecfdf5;
        border: 1px solid #bbf7d0;
        border-radius: 12px;
        display: none;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .file-name-text {
        font-size: 12px;
        font-weight: 700;
        color: #047857;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .alert-box {
        display: none;
        border-radius: 14px;
        padding: 14px 16px;
        margin-top: 18px;
        font-size: 12px;
    }

    .alert-success-custom {
        background: #ecfdf5;
        border: 1px solid #bbf7d0;
        color: #047857;
    }

    .alert-error-custom {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #b91c1c;
    }

    .upload-info-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 16px;
    }

    .upload-info-card i {
        font-size: 23px;
    }

    .upload-info-title {
        font-size: 12px;
        font-weight: 800;
        color: #334155;
        margin-top: 7px;
    }

    .upload-info-text {
        font-size: 11px;
        color: #64748b;
        margin-top: 3px;
        line-height: 1.5;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #fff7ed;
        color: #c2410c;
        padding: 9px 15px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .04em;
    }

    @media(max-width: 768px) {
        main {
            padding: 20px !important;
        }
    }
</style>


<main class="flex-1 p-4 md:p-12 space-y-8 overflow-y-auto">

    <div class="max-w-7xl mx-auto">

        <!-- PAGE HEADER -->
        <div class="mb-10">

            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-5">

                <div>

                    <h1 class="text-3xl md:text-4xl font-extrabold text-slate-800 tracking-tight">

                        Bulk Property

                        <span class="text-emerald-600">
                            Upload
                        </span>

                    </h1>

                    <p class="text-slate-500 mt-2 text-sm md:text-base">

                        Upload property assessment data in bulk for ARV processing.

                    </p>

                </div>


                <div>

                    <span class="status-badge">

                        <i class="bx bx-cloud-upload"></i>

                        DATA UPLOAD ONLY

                    </span>

                </div>

            </div>

        </div>


        <!-- MAIN CONTENT -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">


            <!-- TEMPLATE CARD -->
            <div class="glass-card p-6 md:p-10 flex flex-col justify-between">

                <div>

                    <div class="w-20 h-20 bg-emerald-50 text-emerald-600 rounded-3xl flex items-center justify-center mb-8 shadow-sm">

                        <i class="fa fa-file-excel-o text-4xl"></i>

                    </div>


                    <h3 class="text-2xl font-bold text-slate-800 mb-4">

                        1. Download Upload Template

                    </h3>


                    <p class="text-slate-500 text-sm leading-relaxed mb-8">

                        Download the official CSV template and enter your
                        property data according to the required columns.
                        The uploaded data will be stored for further ARV processing.

                    </p>


                    <!-- NOTICE -->
                    <div class="bg-emerald-600 p-5 rounded-2xl shadow-lg shadow-emerald-100 flex items-start gap-4">

                        <div class="bg-white/20 p-2 rounded-lg">

                            <i class="bx bx-info-circle text-white text-xl"></i>

                        </div>


                        <div class="text-white">

                            <p class="text-[10px] font-black uppercase tracking-widest opacity-80 mb-1">

                                Important Notice

                            </p>


                            <p class="text-xs font-medium leading-relaxed">

                                Do not change, delete or rename the header
                                columns in the downloaded template.

                            </p>

                        </div>

                    </div>

                </div>


                <!-- DOWNLOAD -->
                <a
                    href="download_arv_excel_template.php"
                    class="mt-8 w-full py-5 px-6 rounded-2xl border-2 border-emerald-100 text-emerald-700 font-bold hover:bg-emerald-50 transition text-center flex items-center justify-center gap-3">

                    <i class="bx bxs-cloud-download text-2xl"></i>

                    Download CSV Template

                </a>

            </div>



            <!-- UPLOAD CARD -->
            <div class="glass-card p-6 md:p-10">

                <div class="flex items-center justify-between mb-10">

                    <div class="flex items-center">

                        <div class="w-12 h-12 bg-slate-900 text-white rounded-xl flex items-center justify-center mr-4">

                            <i class="bx bx-upload text-2xl"></i>

                        </div>


                        <div>

                            <h3 class="text-2xl font-bold text-slate-800">

                                2. Upload Data

                            </h3>


                            <p class="text-xs text-slate-500 mt-1">

                                Select your CSV file and upload it directly.

                            </p>

                        </div>

                    </div>


                    <span class="text-[10px] bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full font-bold uppercase tracking-wider">

                        CSV ONLY

                    </span>

                </div>



                <!-- FORM -->
                <form
                    id="uploadForm"
                    enctype="multipart/form-data">


                    <!-- DROPZONE -->
                    <div class="relative mb-8">

                        <label
                            id="dropzone"
                            for="file"
                            class="upload-dropzone relative block w-full py-16 px-6 rounded-[32px] text-center cursor-pointer overflow-hidden">


                            <!-- DEFAULT -->
                            <div id="uploadContent">

                                <i class="bx bx-file-blank text-5xl text-slate-300 mb-4"></i>


                                <p class="text-sm font-bold text-slate-600">

                                    Drag or Select CSV File

                                </p>


                                <p class="text-[10px] text-slate-400 mt-1">

                                    CSV format only

                                </p>

                            </div>


                            <!-- SELECTED -->
                            <div
                                id="fileSelectedArea"
                                class="hidden">

                                <div class="inline-flex items-center justify-center bg-emerald-100 text-emerald-700 p-4 rounded-2xl">

                                    <i class="bx bxs-check-circle text-2xl mr-3"></i>


                                    <div class="text-left">

                                        <p class="text-[10px] font-black uppercase tracking-widest">

                                            Ready to upload

                                        </p>


                                        <p
                                            id="fileNameDisplay"
                                            class="text-sm font-bold truncate max-w-[220px]">

                                        </p>

                                    </div>

                                </div>


                                <p class="text-[10px] text-emerald-600 mt-4 font-bold">

                                    Click to change file

                                </p>

                            </div>


                            <!-- FILE -->
                            <input
                                type="file"
                                name="file"
                                id="file"
                                accept=".csv"
                                required
                                class="hidden">

                        </label>


                        <!-- FILE NAME -->
                        <div
                            id="fileNameBox"
                            class="file-name-box">

                            <div class="flex items-center gap-2 min-w-0">

                                <i class="bx bxs-file text-emerald-600 text-xl"></i>


                                <span
                                    id="selectedFileName"
                                    class="file-name-text">

                                </span>

                            </div>


                            <button
                                type="button"
                                id="removeFile"
                                class="text-red-500 hover:text-red-700">

                                <i class="bx bx-x text-xl"></i>

                            </button>

                        </div>

                    </div>



                    <!-- FILE INFORMATION -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-8">


                        <div class="upload-info-card">

                            <i class="bx bx-file text-emerald-600"></i>

                            <div class="upload-info-title">

                                CSV File

                            </div>

                            <div class="upload-info-text">

                                Only CSV files are accepted.

                            </div>

                        </div>


                        <div class="upload-info-card">

                            <i class="bx bx-data text-blue-600"></i>

                            <div class="upload-info-title">

                                Bulk Records

                            </div>

                            <div class="upload-info-text">

                                Multiple property records can be uploaded together.

                            </div>

                        </div>


                        <div class="upload-info-card">

                            <i class="bx bx-calculator text-purple-600"></i>

                            <div class="upload-info-title">

                                ARV Later

                            </div>

                            <div class="upload-info-text">

                                ARV generation will be done separately.

                            </div>

                        </div>


                    </div>



                    <!-- PROGRESS -->
                    <div
                        id="progressArea"
                        class="hidden mb-8 p-6 bg-slate-50 rounded-2xl border border-slate-100">


                        <div class="flex justify-between items-center mb-3">

                            <span
                                id="progressStatus"
                                class="text-[10px] font-black text-slate-500 uppercase">

                                Uploading Data...

                            </span>


                            <span
                                id="progressPercent"
                                class="text-xs font-bold text-emerald-600">

                                0%

                            </span>

                        </div>


                        <div class="progress-track">

                            <div
                                id="progressBar"
                                class="progress-value">

                            </div>

                        </div>


                        <div
                            id="recordCount"
                            class="text-[10px] text-slate-400 mt-4 text-center">

                            Preparing upload...

                        </div>

                    </div>



                    <!-- RESULT -->
                    <div
                        id="resultArea"
                        class="alert-box">

                    </div>



                    <!-- SUBMIT -->
                    <button
                        type="submit"
                        id="processBtn"
                        class="btn-modern w-full py-5 rounded-2xl font-bold text-sm tracking-widest uppercase flex items-center justify-center gap-3">

                        Upload Bulk Data

                        <i class="bx bx-right-arrow-alt text-xl"></i>

                    </button>


                </form>

            </div>

        </div>



        <!-- CHECKLIST -->
        <div class="glass-card p-6 md:p-12 border-none shadow-none relative overflow-hidden bg-white/30">

            <div class="absolute top-0 right-0 w-64 h-64 bg-emerald-100 rounded-full blur-3xl -mr-32 -mt-32 opacity-20"></div>


            <h4 class="font-black text-slate-800 mb-10 flex items-center gap-3 text-lg uppercase tracking-tight relative z-10">

                <span class="w-8 h-8 bg-emerald-600 text-white rounded-lg flex items-center justify-center">

                    <i class="bx bx-list-check"></i>

                </span>

                Upload Checklist

            </h4>



            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 relative z-10">


                <!-- 1 -->
                <div class="footer-rule-card">

                    <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center mb-4">

                        <i class="bx bx-file"></i>

                    </div>


                    <h5 class="text-sm font-bold text-slate-800 mb-2 uppercase tracking-wide">

                        CSV Format

                    </h5>


                    <p class="text-xs text-slate-500 leading-relaxed">

                        Upload only CSV files using the official template.

                    </p>

                </div>



                <!-- 2 -->
                <div class="footer-rule-card">

                    <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center mb-4">

                        <i class="bx bx-list-ul"></i>

                    </div>


                    <h5 class="text-sm font-bold text-slate-800 mb-2 uppercase tracking-wide">

                        Header Format

                    </h5>


                    <p class="text-xs text-slate-500 leading-relaxed">

                        Keep the first header row exactly as provided in the template.

                    </p>

                </div>



                <!-- 3 -->
                <div class="footer-rule-card">

                    <div class="w-10 h-10 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center mb-4">

                        <i class="bx bx-data"></i>

                    </div>


                    <h5 class="text-sm font-bold text-slate-800 mb-2 uppercase tracking-wide">

                        Property Data

                    </h5>


                    <p class="text-xs text-slate-500 leading-relaxed">

                        Property and assessment information should be entered correctly.

                    </p>

                </div>



                <!-- 4 -->
                <div class="footer-rule-card">

                    <div class="w-10 h-10 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center mb-4">

                        <i class="bx bx-duplicate"></i>

                    </div>


                    <h5 class="text-sm font-bold text-slate-800 mb-2 uppercase tracking-wide">

                        Duplicate Check

                    </h5>


                    <p class="text-xs text-slate-500 leading-relaxed">

                        Duplicate property records will be checked during upload.

                    </p>

                </div>



                <!-- 5 -->
                <div class="footer-rule-card">

                    <div class="w-10 h-10 bg-red-50 text-red-600 rounded-xl flex items-center justify-center mb-4">

                        <i class="bx bx-error-circle"></i>

                    </div>


                    <h5 class="text-sm font-bold text-slate-800 mb-2 uppercase tracking-wide">

                        Data Validation

                    </h5>


                    <p class="text-xs text-slate-500 leading-relaxed">

                        Invalid or incomplete records will be reported after upload.

                    </p>

                </div>



                <!-- 6 -->
                <div class="footer-rule-card">

                    <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center mb-4">

                        <i class="bx bx-calculator"></i>

                    </div>


                    <h5 class="text-sm font-bold text-slate-800 mb-2 uppercase tracking-wide">

                        ARV Generation

                    </h5>


                    <p class="text-xs text-slate-500 leading-relaxed">

                        Uploading data will not generate ARV. Generation will be a separate step.

                    </p>

                </div>


            </div>

        </div>



        <!-- FOOTER -->
        <footer class="text-center mt-8">

            <p class="text-xs font-medium text-slate-400/80">

                © 2025 Deoria Nagar Parishad

                <span class="mx-2">•</span>

                <span class="text-slate-500">

                    Property Tax System v1.0

                </span>

            </p>

        </footer>

    </div>

</main>



<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="js/mystyle.js"></script>


<script>
    document.addEventListener('DOMContentLoaded', function() {

        const form =
            document.getElementById('uploadForm');

        const fileInput =
            document.getElementById('file');

        const dropzone =
            document.getElementById('dropzone');

        const uploadContent =
            document.getElementById('uploadContent');

        const fileSelectedArea =
            document.getElementById('fileSelectedArea');

        const fileNameDisplay =
            document.getElementById('fileNameDisplay');

        const fileNameBox =
            document.getElementById('fileNameBox');

        const selectedFileName =
            document.getElementById('selectedFileName');

        const removeFile =
            document.getElementById('removeFile');

        const processBtn =
            document.getElementById('processBtn');

        const progressArea =
            document.getElementById('progressArea');

        const progressBar =
            document.getElementById('progressBar');

        const progressPercent =
            document.getElementById('progressPercent');

        const progressStatus =
            document.getElementById('progressStatus');

        const recordCount =
            document.getElementById('recordCount');

        const resultArea =
            document.getElementById('resultArea');


        /*
        |--------------------------------------------------------------------------
        | SHOW SELECTED FILE
        |--------------------------------------------------------------------------
        */

        function showFile(file) {

            if (!file) {
                return;
            }


            const extension =
                file.name
                .split('.')
                .pop()
                .toLowerCase();


            if (extension !== 'csv') {

                alert('Please select a CSV file only.');

                fileInput.value = '';

                return;

            }


            uploadContent.classList.add('hidden');

            fileSelectedArea.classList.remove('hidden');

            dropzone.classList.add('has-file');

            fileNameDisplay.textContent =
                file.name;

            selectedFileName.textContent =
                file.name;

            fileNameBox.style.display =
                'flex';

        }


        /*
        |--------------------------------------------------------------------------
        | FILE SELECT
        |--------------------------------------------------------------------------
        */

        fileInput.addEventListener(
            'change',
            function() {

                if (this.files.length) {

                    showFile(
                        this.files[0]
                    );

                }

            }
        );


        /*
        |--------------------------------------------------------------------------
        | DRAG OVER
        |--------------------------------------------------------------------------
        */

        dropzone.addEventListener(
            'dragover',
            function(e) {

                e.preventDefault();

                dropzone.classList.add(
                    'dragover'
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | DRAG LEAVE
        |--------------------------------------------------------------------------
        */

        dropzone.addEventListener(
            'dragleave',
            function() {

                dropzone.classList.remove(
                    'dragover'
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | DROP
        |--------------------------------------------------------------------------
        */

        dropzone.addEventListener(
            'drop',
            function(e) {

                e.preventDefault();

                dropzone.classList.remove(
                    'dragover'
                );


                if (!e.dataTransfer.files.length) {
                    return;
                }


                const file =
                    e.dataTransfer.files[0];


                if (
                    !file.name
                    .toLowerCase()
                    .endsWith('.csv')
                ) {

                    alert(
                        'Please select a CSV file only.'
                    );

                    return;

                }


                const dataTransfer =
                    new DataTransfer();

                dataTransfer.items.add(file);

                fileInput.files =
                    dataTransfer.files;


                showFile(file);

            }
        );


        /*
        |--------------------------------------------------------------------------
        | REMOVE FILE
        |--------------------------------------------------------------------------
        */

        removeFile.addEventListener(
            'click',
            function(e) {

                e.preventDefault();

                e.stopPropagation();


                fileInput.value = '';


                uploadContent.classList.remove(
                    'hidden'
                );

                fileSelectedArea.classList.add(
                    'hidden'
                );

                dropzone.classList.remove(
                    'has-file'
                );

                fileNameBox.style.display =
                    'none';

            }
        );


        /*
        |--------------------------------------------------------------------------
        | FORM SUBMIT
        |--------------------------------------------------------------------------
        */

        form.addEventListener(
            'submit',
            function(e) {

                e.preventDefault();


                /*
                | File required
                */

                if (!fileInput.files.length) {

                    alert(
                        'Please select a CSV file.'
                    );

                    return;

                }


                /*
                | Form Data
                */

                const formData =
                    new FormData(form);


                /*
                | Button
                */

                processBtn.disabled = true;

                processBtn.innerHTML =
                    '<i class="bx bx-loader-alt bx-spin"></i> Uploading Data...';


                /*
                | Progress
                */

                progressArea.classList.remove(
                    'hidden'
                );


                resultArea.style.display =
                    'none';


                progressBar.style.width =
                    '10%';

                progressPercent.innerText =
                    '10%';

                progressStatus.innerText =
                    'Uploading CSV...';

                recordCount.innerText =
                    'Sending file to server...';


                /*
                |--------------------------------------------------------------------------
                | AJAX
                |--------------------------------------------------------------------------
                */

                $.ajax({

                    url: 'import_bulk.php',

                    type: 'POST',

                    data: formData,

                    contentType: false,

                    processData: false,

                    dataType: 'json',


                    /*
                    |--------------------------------------------------------------------------
                    | Upload Progress
                    |--------------------------------------------------------------------------
                    */

                    xhr: function() {

                        const xhr =
                            new XMLHttpRequest();


                        xhr.upload.addEventListener(
                            'progress',
                            function(e) {

                                if (
                                    e.lengthComputable
                                ) {

                                    let percent =
                                        Math.round(
                                            (
                                                e.loaded /
                                                e.total
                                            ) * 90
                                        );


                                    percent =
                                        Math.max(
                                            10,
                                            percent
                                        );


                                    progressBar.style.width =
                                        percent + '%';

                                    progressPercent.innerText =
                                        percent + '%';

                                }

                            }
                        );


                        return xhr;

                    },


                    /*
                    |--------------------------------------------------------------------------
                    | SUCCESS
                    |--------------------------------------------------------------------------
                    */

                    success: function(res) {

                        progressBar.style.width =
                            '100%';

                        progressPercent.innerText =
                            '100%';


                        if (
                            res.status ===
                            'success'
                        ) {


                            progressStatus.innerText =
                                'Upload Completed';


                            recordCount.innerText =
                                'Records uploaded: ' +
                                (
                                    res.inserted ??
                                    0
                                );


                            resultArea.className =
                                'alert-box alert-success-custom';


                            resultArea.innerHTML =
                                '<i class="bx bx-check-circle me-1"></i> ' +
                                (
                                    res.message ||
                                    'Bulk data uploaded successfully.'
                                );


                            resultArea.style.display =
                                'block';


                            processBtn.disabled =
                                false;


                            processBtn.innerHTML =
                                'Upload Another File ' +
                                '<i class="bx bx-refresh"></i>';


                        } else {


                            progressStatus.innerText =
                                'Upload Failed';


                            recordCount.innerText =
                                'Please correct the CSV and try again.';


                            resultArea.className =
                                'alert-box alert-error-custom';


                            resultArea.innerHTML =
                                '<i class="bx bx-error-circle me-1"></i> ' +
                                (
                                    res.message ||
                                    'Unable to upload data.'
                                );


                            resultArea.style.display =
                                'block';


                            processBtn.disabled =
                                false;


                            processBtn.innerHTML =
                                'Retry Upload ' +
                                '<i class="bx bx-refresh"></i>';

                        }

                    },


                    /*
                    |--------------------------------------------------------------------------
                    | ERROR
                    |--------------------------------------------------------------------------
                    */

                    error: function(xhr) {

                        progressBar.style.width =
                            '100%';

                        progressPercent.innerText =
                            'Error';

                        progressStatus.innerText =
                            'Server Error';


                        let message =
                            'Unable to connect with upload server.';


                        if (
                            xhr.responseJSON &&
                            xhr.responseJSON.message
                        ) {

                            message =
                                xhr.responseJSON.message;

                        }


                        resultArea.className =
                            'alert-box alert-error-custom';


                        resultArea.innerHTML =
                            '<i class="bx bx-error-circle me-1"></i> ' +
                            message;


                        resultArea.style.display =
                            'block';


                        processBtn.disabled =
                            false;


                        processBtn.innerHTML =
                            'Retry Upload ' +
                            '<i class="bx bx-refresh"></i>';

                    }

                });

            }
        );

    });
</script>


<?php

include "./include/footer.php";

?>