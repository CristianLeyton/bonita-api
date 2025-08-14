<!DOCTYPE html>
<html>

<head>
    <title>Nuevo Pedido</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
        }

        .content {
            padding: 20px;
            padding-bottom: 0px;
            background-color: #ffffff;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 14px;
        }

        .coupon-info {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }

        .coupon-info h3 {
            color: #155724;
            margin: 0 0 10px 0;
            font-size: 16px;
        }

        .coupon-info p {
            color: #155724;
            margin: 5px 0;
        }

        .footer {
            font-weight: bold;
            padding: 20px;
            padding-top: 10px;
            text-align: center;
            font-size: 13px;
            color: #666;
        }

        .footer-2 {
            padding: 10px;
            background-color: #f8f9fa;
            text-align: center;
            font-size: 15px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Tu pedido ha sido recibido</h1>
        </div>
        <div class="content">
            <hr>
            <p>Hemos recibido tu pedido. Aquí están los detalles:</p>
            <hr>
            <div style="white-space: pre-line;">{!! $mailMessage !!}</div>
            <hr>

            @if (isset($couponInfo) && $couponInfo)
                <div class="coupon-info">
                    <h3>🎉 ¡Cupón Aplicado!</h3>
                    <p><strong>Cupón:</strong> {{ $couponInfo['code'] }}</p>
                    <p><strong>Descuento:</strong> {{ $couponInfo['discount_percentage'] }}%</p>
                    <p><strong>Subtotal:</strong> ${{ number_format($couponInfo['subtotal'], 2) }}</p>
                    <p><strong>Descuento aplicado:</strong> -${{ number_format($couponInfo['discount_amount'], 2) }}</p>
                    <p><strong>Total Final:</strong> ${{ number_format($couponInfo['final_total'], 2) }}</p>
                </div>
            @endif

            <p>Por favor no realices una transferencia hasta que no te enviemos el costo del envio</p>
            <p>Nos pondremos en contacto contigo pronto para confirmar tu pedido.</p>
        </div>

        <div class="footer">
            <hr>
            <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
            <p>Si tienes alguna pregunta, no dudes en contactarnos. </p>
            <p>¡Gracias por tu compra!</p>
            <hr>
        </div>

        <div class='footer-2'>
            <p>Correo: <a href="mailto:bonnitaglam@gmail.com">bonnitaglam@gmail.com</a></p>
            <p>Instagram: <a href="https://www.instagram.com/bonnita.glam/">bonnita.glam</a></p>
            <p>WhatsApp: <a href="https://wa.me/5493872571890">+57 387 257-1890</a></p>
        </div>

    </div>
</body>

</html>
