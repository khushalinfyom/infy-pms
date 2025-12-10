<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Password Reset</title>
</head>

<body style="background-color:#f4f4f7; padding:30px; font-family:Arial, Helvetica, sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px; margin:auto; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.08);">
        <tr>
            <td style="padding:30px 20px; text-align:center; background:#ffffff;">
                <img src="{{ !empty(getLogoUrl()) ? getLogoUrl() : asset('assets/img/logo-red-black.png') }}"
                     alt="Logo"
                     style="width:80px; height:auto; margin-bottom:10px;">
            </td>
        </tr>

        <tr>
            <td style="padding:0 20px;">
                <hr style="border:none; border-top:1px solid #e5e5e5; margin:0;">
            </td>
        </tr>

        <tr>
            <td style="padding:25px 20px;">
                <p style="font-size:16px; color:#333; margin:0 0 15px;">
                    Dear <strong>{{ ucfirst($username) }}</strong>,
                </p>

                <p style="font-size:14px; color:#555; margin:0 0 15px; line-height:1.6;">
                    We received a request to reset your password for your InfyTracker account.
                    Click the button below to choose a new password.
                </p>
            </td>
        </tr>

        <tr>
            <td style="padding:10px 20px; text-align:center;">
                <a href="{{ $link }}"
                   style="background:#4CAF50; color:#ffffff; padding:12px 25px; font-size:15px;
                          border-radius:6px; text-decoration:none; display:inline-block;">
                    Reset Password
                </a>
            </td>
        </tr>

        <tr>
            <td style="padding:20px;">
                <p style="font-size:14px; color:#555; line-height:1.6; margin:0 0 15px;">
                    This reset link will expire in <strong>60 minutes</strong>.
                </p>

                <p style="font-size:14px; color:#555; line-height:1.6; margin:0 0 15px;">
                    If you did not make this request, you can safely ignore this email.
                </p>

                <p style="font-size:14px; color:#333; margin:0 0 4px;">Regards,</p>
                <p style="font-size:14px; color:#333; margin:0;">InfyTracker Team</p>
            </td>
        </tr>

        <tr>
            <td style="padding:10px 20px;">
                <hr style="border:none; border-top:1px solid #e5e5e5;">
            </td>
        </tr>

        <tr>
            <td style="padding:20px 20px 30px; text-align:left;">
                <p style="font-size:12px; color:#888; line-height:1.6;">
                    If the button above does not work, copy and paste this link into your browser:<br>
                    <a href="{{ $link }}" style="color:#4CAF50; word-break:break-all;">{{ $link }}</a>
                </p>
            </td>
        </tr>
    </table>

</body>

</html>
