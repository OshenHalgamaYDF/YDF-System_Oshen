<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Table</title>
</head>
<body>
  <h1>Buying Price Analysis</h1>
  <form action="create_table.php" method="post">
    <label>Table Name:</label>
    <input type="text" name="table_name" required><br><br>

    <label>Column 1 (name type):</label>
    <input type="text" name="col1" placeholder="id INT PRIMARY KEY AUTO_INCREMENT"><br><br>

    <label>Column 2 (name type):</label>
    <input type="text" name="col2" placeholder="username VARCHAR(50) NOT NULL"><br><br>

    <label>Column 3 (name type):</label>
    <input type="text" name="col3" placeholder="email VARCHAR(100)"><br><br>

    <input type="submit" value="Create Table">
  </form>
</body>
</html>
