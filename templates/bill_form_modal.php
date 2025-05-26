<!-- Overlay form -->
<div id="overlayForm" class="overlay">
    <div class="overlay-content">
        <span class="close-btn" onclick="closeForm()">&times;</span>
        <h1>Add Bill</h1>
        <form id="billForm">

        <input type="hidden" name="invoiceNumber" id="invoiceNumber">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="form-group">
                <label for="buyerName">Buyer Name:</label>
                <input type="text" name="buyerName" id="buyerName" class="form-control">
            </div>

            <div class="form-group">
                <label for="buyerCompany">Buyer Company:</label>
                <input type="text" name="buyerCompany" id="buyerCompany" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="buyerAddress">Buyer Address:</label>
                <input type="text" name="buyerAddress" id="buyerAddress" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="itemName">Item Name:</label>
                <input type="text" name="itemName" id="itemName" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="quantity">Quantity (KG):</label>
                <input type="number" value=0 step="0.01" name="quantity" id="quantity" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="bag">Bag:</label>
                <input type="number" value=0 step="0.01" name="bag" id="bag" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="price">Price (Per KG):</label>
                <input type="number" value=0 step="0.01" name="price" id="price" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="vehicleNumber">Vehicle Number:</label>
                <input type="text" name="vehicleNumber" id="vehicleNumber" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="vehicleFreight">Vehicle Freight:</label>
                <input type="number" value=0 step="0.01" value=0 name="vehicleFreight" id="vehicleFreight" class="form-control">
            </div>

            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
</div>
