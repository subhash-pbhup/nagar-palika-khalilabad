<!DOCTYPE html>
<html>
<head>
  <title>Test Assessment Form</title>
</head>
<body>
  <h2>Test Property Assessment</h2>
  <form action="save_assessment.php" method="POST" enctype="multipart/form-data">
    
    <label>Municipality: </label>
    <input type="text" name="municipality" value="Test Municipality"><br><br>

    <label>Year of Assessment: </label>
    <input type="text" name="assesment_year" value="2025"><br><br>

    <label>Old Holding: </label>
    <input type="text" name="old_holding" value="123"><br><br>

    <label>Property Type: </label>
    <input type="text" name="property_type" value="Residential"><br><br>

    <label>Road: </label>
    <input type="text" name="road" value="Main Road"><br><br>

    <label>Plot Area: </label>
    <input type="text" name="plot_area" value="500"><br><br>

    <label>House No: </label>
    <input type="text" name="house_no" value="12"><br><br>

    <label>Ward: </label>
    <input type="text" name="ward" value="5"><br><br>

    <label>Pincode: </label>
    <input type="text" name="pincode" value="800001"><br><br>

    <label>Building Type: </label>
    <input type="text" name="building_type" value="Pucca"><br><br>

    <label>Latitude: </label>
    <input type="text" name="latitude" value="25.61"><br><br>

    <label>Longitude: </label>
    <input type="text" name="longitude" value="85.14"><br><br>

    <!-- ✅ Floor Data -->
    <h3>Floors</h3>
    <input type="hidden" name="floor_list[0][floor_no]" value="Ground Floor">
    <input type="hidden" name="floor_list[0][residential_type]" value="Fully Residential">
    <input type="hidden" name="floor_list[0][construction_type]" value="RCC">
    <input type="hidden" name="floor_list[0][occupancy_type]" value="Self Occupied">
    <input type="hidden" name="floor_list[0][build_up_area]" value="1200">
    <input type="hidden" name="floor_list[0][usage_type]" value="Residential">
    <input type="hidden" name="floor_list[0][non_residential_group]" value="">
    <input type="hidden" name="floor_list[0][property_name]" value="">

    <input type="hidden" name="floor_list[1][floor_no]" value="1st Floor">
    <input type="hidden" name="floor_list[1][residential_type]" value="Mixed">
    <input type="hidden" name="floor_list[1][construction_type]" value="RCC">
    <input type="hidden" name="floor_list[1][occupancy_type]" value="Rented">
    <input type="hidden" name="floor_list[1][build_up_area]" value="1500">
    <input type="hidden" name="floor_list[1][usage_type]" value="Commercial">
    <input type="hidden" name="floor_list[1][non_residential_group]" value="Shops">
    <input type="hidden" name="floor_list[1][property_name]" value="Sai General Store">

    <br><br>
    <button type="submit">Save Assessment</button>
  </form>
</body>
</html>
