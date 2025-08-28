<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Offel Input</title>
  </head>
  <body>
    <h1>Offel Input</h1>
    <form>
      <div>
        <label>Input Date: </label>
        <input type="date" name="dateOffel" class="form-control" />
      </div>
      <div>
        <label>Input Product: </label>
        <input type="text" name="ProductOffel" class="form-control" />
      </div>
      <div>
        <label>Input Type: </label>
        <input type="text" name="TypeOffel" class="form-control" />
      </div>
      <div>
        <label>Input Buyer: </label>
        <input type="text" name="BuyerOffel" class="form-control" />
      </div>
      <div>
        <label>Input Price: </label>
        <input type="text" name="PriceOffel" class="form-control" />
      </div>
      <div>
        <label>Remarks: </label>
        <input type="text" name="RemarkOffel" class="form-control" />
      </div>
      <div>
        <button type="submit" name="SubmitOffel">Add</button>
      </div>
    </form>

    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Product</th>
          <th>Type</th>
          <th>Buyer</th>
          <th>Price</th>
          <th>Remarks</th>
        </tr>
      </thead>
      <td>
        <tr>
          <td>2024-01-01</td>
          <td>Product A</td>
          <td>Type 1</td>
          <td>Buyer X</td>
          <td>$100</td>
          <td>First order</td>
        </tr>
        <tr>
          <td>2024-01-02</td>
          <td>Product B</td>
          <td>Type 2</td>
          <td>Buyer Y</td>
          <td>$200</td>
          <td>Second order</td>
        </tr>
      </td>
    </table>
  </body>
</html>
