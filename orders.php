<h2>🛒 All Orders</h2>

<table>
    <tr>
        <th>Customer</th>
        <th>Food</th>
        <th>Quantity</th>
        <th>Status</th>
        <th>Action</th>
    </tr>

<?php
// fetch orders
$orders = $conn->query("
    SELECT orders.id, orders.customer_name, foods.name AS food, orders.quantity, orders.status
    FROM orders
    JOIN foods ON orders.food_id = foods.id
");

while($o = $orders->fetch()){
?>
    <tr>
        <form method="POST">
            <td><?php echo $o['customer_name']; ?></td>
            <td><?php echo $o['food']; ?></td>
            <td><?php echo $o['quantity']; ?></td>

            <td>
                <select name="status">
                    <option value="pending" <?php if($o['status']=='pending') echo 'selected'; ?>>Pending</option>
                    <option value="delivered" <?php if($o['status']=='delivered') echo 'selected'; ?>>Delivered</option>
                </select>
            </td>

            <td>
                <input type="hidden" name="id" value="<?php echo $o['id']; ?>">
                <button name="update_status">Update</button>
            </td>
        </form>
    </tr>
<?php } ?>

</table>

<?php
// 🔄 HANDLE UPDATE STATUS
if(isset($_POST['update_status'])){
    $id = $_POST['id'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
    $stmt->execute([$status, $id]);

    echo "<p>✅ Order status updated!</p>";

    // refresh page
    header("Refresh:1");
}
?>