<html lang="en" style="background: #F1F3F5; height: 100% !important; margin: 0; padding: 0; width: 100% !important">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
</head>

<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" style="background: #F1F3F5; height: 100% !important; margin: 0 auto; padding: 0; width: 100% !important" bgcolor="#F1F3F5">
	<table bgcolor="#F1F3F5" cellpadding="0" cellspacing="0" border="0" height="100%" width="100%" style="border: 0; border-collapse: collapse; border-spacing: 0 !important; margin: 0 auto; mso-table-lspace: 0pt; mso-table-rspace: 0pt; vertical-align: top">
		<tr>
			<td style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; vertical-align: top" valign="top">
				<table border="0" width="600" cellpadding="0" cellspacing="0" align="center" style="background: #F1F3F5; border: 0; border-collapse: collapse; border-spacing: 0 !important; margin: auto; mso-table-lspace: 0pt; mso-table-rspace: 0pt; vertical-align: top; width: 600px" bgcolor="#F1F3F5">
					{{--<tr>
          	<td height="40" style="font-size: 0; line-height: 0; mso-table-lspace: 0pt; mso-table-rspace: 0pt; vertical-align: top" valign="top"> </td>
          </tr>
          <tr>
          	<td style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; vertical-align: top" valign="top">
          		<table border="0" width="600" cellpadding="0" cellspacing="0" align="center" style="background: #F1F3F5; border: 0; border-collapse: collapse; border-spacing: 0 !important; margin: 0 auto; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%" bgcolor="#F1F3F5">
          			<tr>
          				<td style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; vertical-align: top" valign="top">
          					<table border="0" width="600" cellpadding="0" cellspacing="0" align="center" style="border: 0; border-collapse: collapse; border-spacing: 0 !important; margin: 0 auto; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%">
          						<tr>
          							<td align="center" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; vertical-align: top" valign="top">
          								<a href="{{ route('dashboard') }}" style="border: 0; color: #005689; text-decoration: none; font-size: 33px; font-weight: bold;">
          									{{ \Config::get('app.name') }}
          								</a>
          							</td>
          						</tr>
          					</table>
          				</td>
          			</tr>
          		</table>
          	</td>
          </tr>--}}
          <tr>
          	<td height="40" style="font-size: 0; line-height: 0; mso-table-lspace: 0pt; mso-table-rspace: 0pt; vertical-align: top" valign="top"> </td>
          </tr>

          <tr>
          	<td style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; vertical-align: top" valign="top">
          		<table border="0" width="600" cellpadding="0" cellspacing="0" align="center" style="background: #ffffff; border: 0; border-collapse: collapse; border-spacing: 0 !important; margin: 0 auto; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%" bgcolor="#ffffff">
          			<tr>
          				<td style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; padding: 40px 50px 30px; vertical-align: top" valign="top">

                  @if (isset($slot))
                    {{ Illuminate\Mail\Markdown::parse($slot) }}
                  @else
          				  @yield('content')
                  @endif

          				</td>
          			</tr>

          		</table>
						</td>
					</tr>
					<tr>
						<td style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; vertical-align: top" valign="top"><table border="0" width="600" cellpadding="0" cellspacing="0" align="center" style="background: #F1F3F5; border: 0; border-collapse: collapse; border-spacing: 0 !important; margin: 0 auto; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%" bgcolor="#F1F3F5">
							<tr>
								<td height="25" style="font-size: 0; line-height: 0; mso-table-lspace: 0pt; mso-table-rspace: 0pt; vertical-align: top" valign="top"> </td>
							</tr>
							<tr>
								<td align="center" style="color: #8c93a0; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; line-height: 22px; mso-table-lspace: 0pt; mso-table-rspace: 0pt; text-align: center; vertical-align: top" valign="top">
								&copy; {{ date('Y') }} <a href="{{ \Config::get('app.freescout_url') }}" style="border: 0; color: #8c93a0; text-decoration: underline">{{ \Config::get('app.name') }}</a> — {{ __('Free open source help desk &amp; shared mailbox' ) }}
								</td>
							</tr>
							<tr>
								<td height="25" style="font-size: 0; line-height: 0; mso-table-lspace: 0pt; mso-table-rspace: 0pt; vertical-align: top" valign="top"> </td>
							</tr>
						</table>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>