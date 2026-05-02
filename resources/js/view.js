function loadReceipt(invoiceNum) {
    const iframe = document.getElementById('receiptFrame');
    // This updates the iframe to load the specific receipt
    iframe.src = 'get_receipt.php?invoice=' + invoiceNum;
}