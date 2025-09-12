<?php 
include('C:\xampp\htdocs\ydf-system-oshen\app\controllers\accounting\buyingpriceanalysis\BuyingPriceAnalysisController.php');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>Buying Price Analysis</title>
  </head>
  <body>
    <h1>Buying Price Analysis</h1>
    <form method="post">
      <!-- s -->
      <input type="hidden" name="product_code" required />

      <label>Date:</label>
      <input type="date" name="date" required />
      <br /><br />

      <label>Product Name:</label>
      <select name="product_name" required onchange="fillProductDetails()">
        <option value="">Select Product</option>
        <?php foreach ($products as $product): ?>
        <option
          value="<?php echo htmlspecialchars($product['product_name']); ?>"
          data-product-code="<?php echo htmlspecialchars($product['product_code']); ?>"
          data-scientific-name="<?php echo htmlspecialchars($product['scientific_name']); ?>"
        >
          <?php echo htmlspecialchars($product['product_name']); ?>
        </option>
        <?php endforeach; ?>
      </select>
      <br /><br />

      <!-- <label>Scientific Name:</label> -->
      <input type="hidden" name="scientific_name" />

      <label>Size Range:</label>
      <input type="text" name="size_range" required />
      <br /><br />

      <label>Specification:</label>
      <input type="text" name="specification" required />
      <br /><br />

      <label>Target Price:</label>
      <input type="number" step="0.01" name="target_price" required />
      <br /><br />

      <label>Buyer Name:</label>
      <input type="text" name="buyer_name" required />
      <br /><br />

      <label>Sold Price:</label>
      <input type="number" step="0.01" name="sold_price" required />
      <br /><br />

      <button
        type="submit"
        name="SubmitOffel"
        class="btn btn-primary px-4"
        id="modalSubmitBtn"
      >
        Add
      </button>
    </form>
    <script>
      function fillProductDetails() {
        const productSelect = document.querySelector(
          'select[name="product_name"]'
        );
        const selectedOption =
          productSelect.options[productSelect.selectedIndex];

        if (selectedOption.dataset.productCode) {
          document.querySelector('input[name="product_code"]').value =
            selectedOption.dataset.productCode;
        }
        if (selectedOption.dataset.scientificName) {
          document.querySelector('input[name="scientific_name"]').value =
            selectedOption.dataset.scientificName;
        }
      }
    </script>
  </body>
</html>
