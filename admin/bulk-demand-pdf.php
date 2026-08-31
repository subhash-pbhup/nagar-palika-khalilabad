<?php
ob_start();
ini_set('display_errors', 0); // ZIP फाइल करप्ट न हो इसलिए एरर्स ऑफ रखे हैं
error_reporting(E_ALL);
ini_set('memory_limit', '2048M');
set_time_limit(0);

require_once 'db.php';
require_once __DIR__ . '/arv/arv_engine.php';
require_once __DIR__ . '/mpdf-build/vendor/autoload.php';

mysqli_set_charset($conn, "utf8mb4");

/* ================= FETCH DATA ================= */

$ward = mysqli_real_escape_string($conn, $_GET['ward']);
$property_type = mysqli_real_escape_string($conn, $_GET['property_type']);

$query = "SELECT p.*, a.id as assessment_id, a.ward, a.new_holding, a.house_no, a.addr1,
          a.addr2,
          a.khasra_no, a.road, a.center_image_gps, a.property_type, a.building_type,
          o.owner_name, o.father_husband_pan, o.mobile 
          FROM property_arv_details p 
          INNER JOIN assessments a ON a.id = p.assessment_id 
          LEFT JOIN assessment_owners o ON o.assessment_id = a.id 
          WHERE p.arv_status IN ('bulk','GBW') 
          AND a.ward = '$ward' 
          AND a.property_type = '$property_type' 
          AND (a.is_deleted = 0 OR a.is_deleted IS NULL) 
          ORDER BY a.id ASC";

$result = mysqli_query($conn, $query);
$total_records = mysqli_num_rows($result);

if ($total_records == 0) {
    ob_end_clean();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8"><title>No Records</title>
<style>
    body{ margin:0; padding:0; font-family: Arial, sans-serif; background:#f4f6f9; display:flex; justify-content:center; align-items:center; height:100vh; }
    .card{ background:#ffffff; padding:40px 50px; border-radius:12px; box-shadow:0 8px 25px rgba(0,0,0,0.1); text-align:center; width:400px; }
    .btn{ display:inline-block; padding:10px 25px; background:rgb(16 181 126); color:#fff; text-decoration:none; border-radius:6px; font-weight:bold; }
</style>
</head>
<body>
<div class="card"><h2>No records found.</h2><a href="view-assesment.php" class="btn">Go Back</a></div>
</body>
</html>
<?php
exit;
}

/* ================= ZIP SETUP ================= */

$temp_folder = __DIR__."/temp_notices_".time();
if(!file_exists($temp_folder)){
    mkdir($temp_folder, 0777, true);
}

$zip_name = "Demand_Ward_".$ward."_".time().".zip";
$zip_path = $temp_folder."/".$zip_name;

$zip = new ZipArchive();
if ($zip->open($zip_path, ZipArchive::CREATE) !== TRUE) {
    exit("Cannot open <$zip_path>\n");
}

/* ================= LOOP ================= */

while ($row = mysqli_fetch_assoc($result)) {

    $assessment_id = $row['assessment_id'];

    try {
        $calc = calculateARV($assessment_id, $conn);
        $arv_total  = (float)$calc['total_arv'];
        $house_tax  = (float)$calc['house_tax_arv'];
        $water_tax  = (float)$calc['water_tax_arv'];
        $total_tax  = $house_tax + $water_tax;
    } catch (Exception $e) {
        $arv_total = 0; $house_tax = 0; $water_tax = 0; $total_tax = 0;
    }

    /* ========= IMAGE FIX ========= */
    $image_path = (!empty($row['center_image_gps']) && file_exists($row['center_image_gps']))
        ? $row['center_image_gps'] : '';

    $image_html = $image_path
        ? "<img src='{$image_path}' style='max-height:180px; max-width:100%;'>"
        : "<div style='margin-top:80px;font-size:12px;'>CENTER PROPERTY IMAGE</div>";

    // आपका ओरिजिनल HTML डिजाइन (पूरा टेक्स्ट और सिग्नेचर के साथ)
    $html = '
    <meta charset="utf-8">
    <style> body { font-family: dejavusans; } </style>
    <div style="border:2px solid #000; padding:12px; min-height:950px; font-family:notodeva; font-size:11px;">
        <table width="100%" style="border-bottom:2px solid #000;">
            <tr>
                <td width="20%"><img src="logo-main.png" width="80"></td>
                <td width="60%" align="center">
                    <div style="font-size:22px; font-weight:bold; color:darkgreen;">नगर पालिका परिषद देवरिया (उ.प्र.)</div>
                    <div style="font-size:12px; font-weight:bold;">उत्तर प्रदेश पालिका अधिनियम 1916 की धारा 129 (क) के अधीन-नोटिस</div>
                </td>
                <td width="20%" align="right"><img src="sw-logo.png" width="120"></td>
            </tr>
        </table>
        <br>
        <table width="100%">
            <tr>
                <td width="65%" valign="top">
                    <table width="100%" style="line-height:1.5;">
                        <tr><td width="40%"><b>वार्ड नंबर –</b></td><td style="border-bottom:1px dotted #000;"><b>'.$row['ward'].'</b></td></tr>
                        <tr><td><b>भूमि/भवन संख्या (यदि है तो)</b></td><td style="border-bottom:1px dotted #000;"><b>'.$row['house_no'].'</b></td></tr>
                        <tr><td><b>सर्वे संख्या –</b></td><td style="border-bottom:1px dotted #000;"><b>'.$row['new_holding'].'</b></td></tr>
                        <tr><td><b>भवन स्वामी /अध्यासी का नाम-</b></td><td style="border-bottom:1px dotted #000;"><b>'.$row['owner_name'].'</b></td></tr>
                        <tr><td><b>पिता/पति का नाम-</b></td><td style="border-bottom:1px dotted #000;"><b>'.$row['father_husband_pan'].'</b></td></tr>
                        <tr><td><b>संपत्ति का पता-</b></td><td style="border-bottom:1px dotted #000;"><b>'.(!empty($row['addr1']) ? $row['addr1'] : '').(!empty($row['addr1']) && !empty($row['addr2']) ? ', ' : '').(!empty($row['addr2']) ? $row['addr2'] : '').'</b></td></tr>
                        <tr><td><b>पूर्व मे निर्धारित वार्षिक किराया मूल्य</b></td><td style="border-bottom:1px dotted #000;"><b>₹ '.($row['arv_total'] ?? 0).'</b></td></tr>
                        <tr><td><b>खसरा संख्या-</b></td><td style="border-bottom:1px dotted #000;"><b>'.$row['khasra_no'].'</b></td></tr>
                        <tr><td><b>मोबाईल नंबर-</b></td><td style="border-bottom:1px dotted #000;"><b>'.$row['mobile'].'</b></td></tr>
                        <tr><td><b>रोड का प्रकार-</b></td><td style="border-bottom:1px dotted #000;"><b>'.$row['road'].'</b></td></tr>
                        <tr><td><b>संपत्ति का प्रकार</b></td><td style="border-bottom:1px dotted #000;"><b>'.$property_type.'</b></td></tr>
                        <tr><td><b>निर्माण का प्रकार –</b></td><td style="border-bottom:1px dotted #000;"><b>'.$row['building_type'].'</b></td></tr>
                    </table>
                </td>
                <td width="35%" valign="top" align="center">
                    <div style="border:2px solid #000; width:90%; height:230px; padding:5px;">
                        '.$image_html.'
                        <div style="margin-top:8px; font-size:11px; font-weight:bold;">PROPERTY IMAGE</div>
                    </div>
                </td>
            </tr>
        </table>
        <br>
        <div style="text-align:center; font-weight:bold; font-size:16px;">गणना</div>
        <br>
        <b style="font-size:15px;">संपत्ति का कुल वार्षिक मूल्य :</b>
        <span style="border-bottom:1px dotted #000; padding:0 80px;">₹ <b>'.number_format($arv_total,2).'</b></span>
        <br><br>
        <table width="100%" border="1" cellpadding="5" style="border-collapse:collapse; text-align:center;">
            <tr>
                <th>गृहकर (वार्षिक मूल्य का 10 %)</th>
                <th>जलकर (वार्षिक मूल्य का 10%)</th>
                <th>योग</th>
            </tr>
            <tr>
                <td><b>₹ '.number_format($house_tax,2).'</b></td>
                <td><b>₹ '.number_format($water_tax,2).'</b></td>
                <td><b>₹ '.number_format($total_tax,2).'</b></td>
            </tr>
        </table>
        <br>
        <div style="text-align:justify; line-height:1.6; font-size:14px">
            अतः नियमानुसार सर्वे के माध्यम से प्राप्त सूचना के आधार पर आपके भवन का वार्षिक मूल्य ₹ <b>'.number_format($arv_total,2).'</b> प्रस्तावित किया गया है , जो वर्ष <span style="border-bottom:1px dotted #000; padding:0 40px; font-weight:bold;">2025-2026</span> से गृहकर ₹ <b>'.number_format($house_tax,2).'</b> जलकर ₹ <b>'.number_format($water_tax,2).'</b> कुल रुपया ₹ <b>'.number_format($total_tax,2).'</b> आप द्वारा देय है | प्रस्तावित वार्षिक मूल्य पर किसी भी प्रकार के आपत्ति के लिए 15 कार्यदिवस के अंदर सुधार हेतु प्रार्थना पत्र प्रस्तुत किया जा सकता है |
        </div>
        <br><br><br><br><br><br>
        <table width="100%">
            <tr>
                <td><b>प्राप्तकर्ता के हस्ताक्षर</b></td>
                <td align="right"><b>कर अधीक्षक/राजस्व निरीक्षक के हस्ताक्षर</b></td>
            </tr>
        </table>
    </div>';

    $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4']);
    $mpdf->autoScriptToLang = true;
    $mpdf->autoLangToFont = true;
    $mpdf->WriteHTML($html);

    $pdf_filename = "Notice_".$assessment_id.".pdf";
    $pdf_file_path = $temp_folder."/".$pdf_filename;

    $mpdf->Output($pdf_file_path, \Mpdf\Output\Destination::FILE);
    $zip->addFile($pdf_file_path, $pdf_filename);
}

$zip->close();

/* ================= DOWNLOAD ZIP ================= */

if (ob_get_length()) ob_end_clean();

if (file_exists($zip_path)) {
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="'.$zip_name.'"');
    header('Content-Length: ' . filesize($zip_path));
    header('Pragma: no-cache');
    header('Expires: 0');
    
    readfile($zip_path);

    // सफाई
    foreach(glob($temp_folder."/*.pdf") as $file) unlink($file);
    unlink($zip_path);
    rmdir($temp_folder);
    exit;
} else {
    exit("Error: Zip file could not be created.");
}