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

        .footer {
            font-weight: bold;
            padding: 20px;
            padding-top:10px;
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
