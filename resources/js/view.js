function loadReceipt(invoiceNum) {
    const iframe = document.getElementById('receiptFrame');
    iframe.src = 'get_receipt.php?invoice=' + invoiceNum;
}