$(document).ready(function() {
  $('#billForm').submit(function(event) {
    event.preventDefault(); // Prevent the default form submission

    var invoiceNumber = $('input[name=invoiceNumber]').val(); // Check for invoice number (edit mode)
    console.log("InvoiceNumber: "+invoiceNumber);
    var vechicleFreight = $('input[name=vehicleFreight]').val();
    console.log("vehicleFreight: "+vechicleFreight);
    var billData = {
        csrf_token: $('input[name="csrf_token"]').val(), // Added CSRF token
        invoiceNumber: invoiceNumber, // Include invoiceNumber if updating
      buyerName: $('input[name=buyerName]').val(),
      buyerCompany: $('input[name=buyerCompany]').val(),
      buyerAddress: $('input[name=buyerAddress]').val(),
      itemName: $('input[name=itemName]').val(),
      quantity: parseFloat($('input[name=quantity]').val()),
      price: parseFloat($('input[name=price]').val()),
      bag: parseFloat($('input[name=bag]').val()),
      vehicleNumber: $('input[name=vehicleNumber]').val(),
      vehicleFreight: Number.isNaN(parseFloat(vechicleFreight)) ? 0 : vechicleFreight
      // Add more properties as needed
    };
    
    // Send the form data to the PHP script
    $.ajax({
      url: 'save_bill.php',
      type: 'POST',
      data: { data: billData },
      success: function(response) {
        // Handle the response from the server
        console.log(response);
        Swal.fire(
        response
        ).then(function(){ 
       location.reload();
   });
      },
      error: function(xhr, status, error) {
        // Handle errors
        console.error(error);
        Swal.fire(
        "Failed to save bill"
        )
      }
    });
  });

  // Add new event listener for edit buttons
  // Using event delegation by attaching the listener to a static parent (.table-container)
  $('.table-container').on('click', '.edit-bill-button', function() {
    var billDataString = $(this).data('bill');
    if (billDataString) {
        try {
            // The data attribute is already a JS object/string, direct parsing might not be needed if jQuery handles it.
            // However, json_encode in PHP produces a string that needs parsing.
            var billDataObject = JSON.parse(billDataString); 
            editBill(billDataObject); // Call the existing function that populates the form
        } catch (e) {
            console.error("Error parsing bill data:", e, "Raw data:", billDataString);
            Swal.fire('Error', 'Could not read bill details for editing. Invalid data format.', 'error');
        }
    } else {
        console.error("No bill data found on button.");
        Swal.fire('Error', 'No bill details found for editing.', 'error');
    }
  });
});
        // Function to open the form overlay
        function openForm() {
            document.getElementById("overlayForm").style.display = "block";
        }

        // Function to close the form overlay
        function closeForm() {
            document.getElementById("overlayForm").style.display = "none";
        }

        // Function to open the form overlay for editing a bill with pre-filled details
        function editBill(bill) {
            document.getElementById("overlayForm").style.display = "block";

            // Populate the form with existing bill data for editing
            $('input[name=invoiceNumber]').val(bill.invoice_number); // Hidden field for invoice number
            $('input[name=buyerName]').val(bill.buyer_name);
            $('input[name=buyerCompany]').val(bill.buyer_company);
            $('input[name=buyerAddress]').val(bill.buyer_address);
            $('input[name=itemName]').val(bill.item_name);
            $('input[name=quantity]').val(bill.quantity);
            $('input[name=price]').val(bill.price);
            $('input[name=bag]').val(bill.bag);
            $('input[name=vehicleNumber]').val(bill.vehicle_number);
            $('input[name=vehicleFreight]').val(bill.vehicle_freight);
        }

        // Function to clear the form inputs
        function clearForm() {
        document.getElementById('buyerName').value = '';
        document.getElementById('buyerCompany').value = '';
        document.getElementById('buyerAddress').value = '';
        document.getElementById('itemName').value = '';
        document.getElementById('quantity').value = '';
        document.getElementById('price').value = '';
        document.getElementById('bag').value = '';
        document.getElementById('vehicleNumber').value = '';
        document.getElementById('vehicleFreight').value = '';
        }
