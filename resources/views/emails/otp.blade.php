<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sal7ly Verification Code</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f6f9; padding: 40px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); overflow: hidden;">
                    {{-- Header --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%); padding: 32px 40px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: 700; letter-spacing: 1px;">
                                🔧 Sal7ly
                            </h1>
                            <p style="color: rgba(255,255,255,0.85); margin: 8px 0 0; font-size: 14px;">
                                Your Trusted Service Platform
                            </p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding: 40px;">
                            <p style="color: #374151; font-size: 16px; margin: 0 0 8px; font-weight: 600;">
                                Hello {{ $userName }},
                            </p>
                            <p style="color: #6b7280; font-size: 14px; line-height: 1.6; margin: 0 0 28px;">
                                We received a request to verify your email address. Use the code below to complete your verification:
                            </p>

                            {{-- OTP Code Box --}}
                            <div style="text-align: center; margin: 0 0 28px;">
                                <div style="display: inline-block; background: linear-gradient(135deg, #eff6ff 0%, #f0e7ff 100%); border: 2px dashed #2563eb; border-radius: 12px; padding: 20px 40px;">
                                    <span style="font-size: 36px; font-weight: 800; letter-spacing: 12px; color: #1e40af; font-family: 'Courier New', monospace;">
                                        {{ $code }}
                                    </span>
                                </div>
                            </div>

                            {{-- Expiry Warning --}}
                            <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 8px; padding: 12px 16px; margin: 0 0 28px;">
                                <p style="color: #92400e; font-size: 13px; margin: 0;">
                                    ⏱️ This code expires in <strong>{{ $expiryMinutes }} minutes</strong>. Do not share it with anyone.
                                </p>
                            </div>

                            <p style="color: #6b7280; font-size: 13px; line-height: 1.6; margin: 0;">
                                If you didn't request this code, you can safely ignore this email. Someone may have entered your email address by mistake.
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color: #f9fafb; padding: 24px 40px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="color: #9ca3af; font-size: 12px; margin: 0;">
                                &copy; {{ date('Y') }} Sal7ly. All rights reserved.
                            </p>
                            <p style="color: #9ca3af; font-size: 11px; margin: 8px 0 0;">
                                This is an automated email. Please do not reply.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
