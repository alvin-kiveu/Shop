( function() {
    const registerPaymentMethod = window.wc.wcBlocksRegistry.registerPaymentMethod;

    registerPaymentMethod( {
        name: 'mpesa',
        label: 'Lipa Na M-Pesa',
        canMakePayment: () => true,
        content: () => 'You will be redirected to complete your M-Pesa payment.',
    } );
} )();
