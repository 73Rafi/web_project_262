"""Run against a disposable database, with an existing test admin account.

PORTAL_TEST_ADMIN_EMAIL and PORTAL_TEST_ADMIN_PASSWORD must be set.
Usage: python tests/smoke.py http://127.0.0.1:8000
The test creates users, PDFs, projects and discussions. Never use a real database.
"""
import http.cookiejar
import json
import os
import re
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
import uuid

BASE = (sys.argv[1] if len(sys.argv) > 1 else 'http://127.0.0.1:8000').rstrip('/') + '/'
PASSWORD = 'Test-password-782!'
PDF = b'%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n'
passed = 0


def check(condition, label):
    global passed
    if not condition:
        raise AssertionError(label)
    passed += 1
    print('PASS:', label, flush=True)


class Browser:
    def __init__(self):
        self.cookies = http.cookiejar.CookieJar()
        self.client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.cookies))

    def request(self, page, fields=None, file=None):
        headers = {}
        data = None
        if file:
            boundary = '----PortalTest' + uuid.uuid4().hex
            parts = []
            for key, value in fields.items():
                parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode())
            field, name, content = file
            parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{field}"; filename="{name}"\r\nContent-Type: application/pdf\r\n\r\n'.encode() + content + b'\r\n')
            parts.append(f'--{boundary}--\r\n'.encode())
            data = b''.join(parts)
            headers['Content-Type'] = 'multipart/form-data; boundary=' + boundary
        elif fields is not None:
            data = urllib.parse.urlencode(fields).encode()
        request = urllib.request.Request(urllib.parse.urljoin(BASE, page), data=data, headers=headers)
        try:
            response = self.client.open(request, timeout=20)
        except urllib.error.HTTPError as error:
            response = error
        content = response.read()
        return response.status, response.url, content.decode('utf-8', errors='replace'), content

    def token(self, page):
        status, _, text, _ = self.request(page)
        check(status == 200, page.split('?')[0] + ' loads')
        match = re.search(r'name="csrf" value="([a-f0-9]+)"', text)
        if not match:
            raise AssertionError('CSRF token missing on ' + page)
        return match[1]

    def post(self, page, fields, file=None):
        fields = dict(fields, csrf=self.token(page))
        return self.request(page, fields, file)

    def login(self, email, password=PASSWORD):
        return self.post('signIn.php', {'email': email, 'password': password})


admin = Browser()
status, url, text, _ = admin.login(os.environ['PORTAL_TEST_ADMIN_EMAIL'], os.environ['PORTAL_TEST_ADMIN_PASSWORD'])
check(status == 200 and url.endswith('admin_index.php'), 'admin login')
guest = Browser()
check(guest.request('User_dashboard.php')[1].endswith('signIn.php'), 'guest cannot access dashboard')
check(guest.request('backend/database.sql')[0] == 403, 'database source blocked')
check(guest.request('backend/uploads/1789049244070.pdf')[0] == 403, 'direct upload access blocked')
check(guest.request('register.php', {'email': 'bad'})[0] == 403, 'missing CSRF rejected')

suffix = str(time.time_ns())
new_admin_email = 'admin-' + suffix + '@example.test'
new_admin_fields = {'action': 'create_admin', 'full_name': 'New Test Admin', 'email': new_admin_email, 'password': PASSWORD, 'confirm_password': PASSWORD, 'current_password': 'wrong'}
check('current password is incorrect' in admin.post('admin_index.php', new_admin_fields)[2], 'creating admin requires current password')
new_admin_fields['current_password'] = os.environ['PORTAL_TEST_ADMIN_PASSWORD']
check('New admin account created' in admin.post('admin_index.php', new_admin_fields)[2], 'admin can create another admin')
check('already registered' in admin.post('admin_index.php', new_admin_fields)[2], 'duplicate admin email rejected')
new_admin = Browser()
check(new_admin.login(new_admin_email)[1].endswith('admin_index.php'), 'new admin can log in')
check('Download My CV' not in new_admin.request('profile.php')[2], 'admin without CV has no broken CV link')
email = 'student-' + suffix + '@example.test'
other_email = 'other-' + suffix + '@example.test'
fields = {'fullName': 'Test Student', 'role': 'admin', 'department': 'CSE', 'email': email, 'password': PASSWORD}
check('Choose Student or Teacher' in guest.post('register.php', fields, ('cv', 'cv.pdf', PDF))[2], 'public admin registration rejected')
fields['role'] = 'student'
check('real PDF' in guest.post('register.php', fields, ('cv', 'fake.pdf', b'not a PDF'))[2], 'fake PDF rejected')
invalid = dict(fields, password='')
check('between 8 and 72' in guest.post('register.php', invalid, ('cv', 'cv.pdf', PDF))[2], 'empty password rejected')
check(guest.post('register.php', fields, ('cv', 'cv.pdf', PDF))[1].endswith('signIn.php'), 'registration succeeds')
check('already registered' in guest.post('register.php', fields, ('cv', 'cv.pdf', PDF))[2], 'duplicate email rejected')
check('Invalid email or password' in guest.login(email, 'wrong')[2], 'incorrect password rejected')
check(guest.login(email)[1].endswith('User_dashboard.php'), 'student login')
check(guest.request('admin_index.php')[0] == 403, 'student admin access denied')
check(guest.request('admin_index.php', dict(new_admin_fields, csrf=guest.token('profile.php')))[0] == 403, 'student cannot create admin')
profile = guest.request('profile.php')[2]
user_id = re.search(r'type=cv&amp;id=(\d+)', profile)[1]
check(guest.request('download.php?type=cv&id=' + user_id)[3] == PDF, 'own CV download')

other = Browser()
other.post('register.php', dict(fields, email=other_email, fullName='Other Student'), ('cv', 'cv.pdf', PDF))
other.login(other_email)
check(other.request('download.php?type=cv&id=' + user_id)[0] == 403, 'another user CV denied')

# Private chat: only the two participants may read, send or mark messages read.
other_id = re.search(r'type=cv&amp;id=(\d+)', other.request('profile.php')[2])[1]
check(Browser().request('chat_api.php?action=inbox')[0] == 401, 'anonymous chat API denied')
check('Private Messages' in guest.request('Community_Forum.php')[2], 'forum links to private messages')
check('Other Student' in guest.request('messages.php?q=Other')[2], 'chat people search works')
check(guest.post('messages.php', {'action': 'start', 'recipient_id': user_id})[0] == 422, 'self chat rejected')
status, chat_url, chat_html, _ = guest.post('messages.php', {'action': 'start', 'recipient_id': other_id})
conversation_id = re.search(r'data-conversation-id="(\d+)"', chat_html)[1]
check(status == 200 and int(conversation_id) > 0, 'private conversation created')
check(other.post('messages.php', {'action': 'start', 'recipient_id': user_id})[1] == chat_url, 'reverse pair reuses the same conversation')
student_chat_csrf = guest.token('messages.php')
other_chat_csrf = other.token('messages.php')
admin_chat_csrf = admin.token('messages.php')
chat_fields = {'action': 'send', 'conversation_id': conversation_id, 'body': '<script>alert(1)</script>\nHello বাংলা 👋', 'message_token': uuid.uuid4().hex}
check(guest.request('chat_api.php', chat_fields)[0] == 403, 'chat send requires CSRF')
check(admin.request('chat_api.php?action=messages&conversation_id=' + conversation_id)[0] == 404, 'admin cannot read someone else\'s chat')
check(admin.request('messages.php?conversation_id=' + conversation_id)[0] == 404, 'unrelated user cannot open private chat page')
check(admin.request('chat_api.php', dict(chat_fields, csrf=admin_chat_csrf))[0] == 404, 'unrelated user cannot send into a private chat')
check(admin.request('chat_api.php', {'action': 'read', 'conversation_id': conversation_id, 'up_to': '2147483647', 'csrf': admin_chat_csrf})[0] == 404, 'unrelated user cannot mark private chat read')
status, _, sent, _ = guest.request('chat_api.php', dict(chat_fields, csrf=student_chat_csrf))
message_id = json.loads(sent)['message']['id']
check(status == 200, 'chat message sent')
retry = json.loads(guest.request('chat_api.php', dict(chat_fields, csrf=student_chat_csrf))[2])
check(retry['message']['id'] == message_id, 'retrying a send does not duplicate a message')
received = json.loads(other.request('chat_api.php?action=messages&conversation_id=' + conversation_id + '&after_id=0')[2])
check(len(received['messages']) == 1 and received['messages'][0]['body'] == chat_fields['body'], 'recipient receives text, newlines and Unicode')
inbox = json.loads(other.request('chat_api.php?action=inbox')[2])['conversations']
check(inbox[0]['unread_count'] == 1, 'new message increments unread count')
rendered_chat = other.request('messages.php?conversation_id=' + conversation_id)[2]
check('&lt;script&gt;alert(1)&lt;/script&gt;' in rendered_chat and '<script>alert(1)</script>' not in rendered_chat, 'chat text is HTML escaped')
check(json.loads(other.request('chat_api.php?action=inbox')[2])['conversations'][0]['unread_count'] == 1, 'reading API alone does not mark a message seen')
check(other.request('chat_api.php', {'action': 'read', 'conversation_id': conversation_id, 'up_to': message_id})[0] == 403, 'mark-read requires CSRF')
other.request('chat_api.php', {'action': 'read', 'conversation_id': conversation_id, 'up_to': message_id, 'csrf': other_chat_csrf})
seen = json.loads(guest.request('chat_api.php?action=messages&conversation_id=' + conversation_id + '&after_id=' + str(message_id))[2])
check(seen['peer_read_up_to'] == message_id, 'sender receives Seen status')
check(json.loads(other.request('chat_api.php?action=inbox')[2])['conversations'][0]['unread_count'] == 0, 'mark-read clears unread count')
for bad_body in ['', ' ' * 10, 'a' * 2001, '👋' * 2001]:
    check(guest.request('chat_api.php', dict(chat_fields, csrf=student_chat_csrf, body=bad_body, message_token=uuid.uuid4().hex))[0] == 422, 'invalid or oversized chat message rejected')
check(guest.request('chat_api.php?action=messages&conversation_id[]=1')[0] == 400, 'invalid chat ID rejected')
check(other.request('chat_api.php', dict(chat_fields, csrf=other_chat_csrf, body='Hello back', message_token=uuid.uuid4().hex))[0] == 200, 'recipient can reply')
for number in range(103):
    status = guest.request('chat_api.php', dict(chat_fields, csrf=student_chat_csrf, body='History ' + str(number), message_token=uuid.uuid4().hex))[0]
    if status != 200: raise AssertionError('chat history setup failed')
latest = json.loads(guest.request('chat_api.php?action=messages&conversation_id=' + conversation_id)[2])
check(len(latest['messages']) == 50 and latest['has_more'], 'chat loads the latest 50 messages')
older = json.loads(guest.request('chat_api.php?action=messages&conversation_id=' + conversation_id + '&before_id=' + str(latest['messages'][0]['id']))[2])
check(len(older['messages']) == 50 and older['has_more'] and older['messages'][-1]['id'] < latest['messages'][0]['id'], 'older history pagination has no overlap')
forward = json.loads(other.request('chat_api.php?action=messages&conversation_id=' + conversation_id + '&after_id=0')[2])
check(len(forward['messages']) == 100 and forward['has_more'], 'live polling bounds each response to 100 messages')
remaining = json.loads(other.request('chat_api.php?action=messages&conversation_id=' + conversation_id + '&after_id=' + str(forward['messages'][-1]['id']))[2])
check(len(remaining['messages']) == 5 and not remaining['has_more'], 'live cursor receives all remaining messages exactly once')
check([m['id'] for m in forward['messages']] == sorted(m['id'] for m in forward['messages']), 'chat history is chronological')
admin.post('admin_index.php', {'action': 'user', 'id': other_id, 'active': '0'})
check(guest.request('chat_api.php', dict(chat_fields, csrf=student_chat_csrf, message_token=uuid.uuid4().hex))[0] == 403, 'cannot send to a disabled account')
check(other.request('chat_api.php?action=inbox')[0] == 401, 'disabled account loses chat API access')
check(guest.request('messages.php?conversation_id=' + conversation_id)[0] == 200, 'existing chat remains readable when recipient is disabled')
admin.post('admin_index.php', {'action': 'user', 'id': other_id, 'active': '1'})
other.login(other_email)

paper_title = 'Integration Paper ' + suffix
paper_fields = {'title': paper_title, 'authors': 'Test Student', 'abstract': '<script>alert(1)</script> Research abstract', 'keywords': 'Testing', 'department': 'CSE', 'category': 'Computer Science', 'action': 'publish'}
profile = guest.post('upload.php', paper_fields, ('paper', 'paper.pdf', PDF))[2]
check(paper_title in profile and 'Pending' in profile, 'paper persists as pending')
paper_id = re.search(r'type=paper&amp;id=(\d+)', profile)[1]
check(other.request('download.php?type=paper&id=' + paper_id)[0] == 404, 'pending paper private')
check(paper_title not in other.request('research-exploer1.php')[2], 'pending paper absent from public list')
admin.post('admin_index.php', {'action': 'paper', 'id': paper_id, 'decision': 'approved'})
research = other.request('research-exploer1.php?q=' + urllib.parse.quote(paper_title))[2]
check(paper_title in research and '&lt;script&gt;' in research and '<script>alert(1)</script>' not in research, 'approved paper searchable and HTML escaped')
check(other.request('download.php?type=paper&id=' + paper_id)[3] == PDF, 'approved paper downloadable')
other.post('research-exploer1.php', {'paper_id': paper_id, 'action': 'save'})
check(paper_title in other.request('research-exploer1.php?saved=1')[2], 'saved paper persists')
other.post('research-exploer1.php', {'paper_id': paper_id, 'action': 'unsave'})
check(paper_title not in other.request('research-exploer1.php?saved=1')[2], 'unsave persists')
check(paper_title in guest.request('Home_page.php')[2], 'homepage uses database papers')

draft_title = 'Draft ' + suffix
profile = guest.post('upload.php', dict(paper_fields, title=draft_title, action='draft'), ('paper', 'draft.pdf', PDF))[2]
draft_id = re.search(r'type=paper&amp;id=(\d+)', profile)[1]
check('>Draft</span>' in profile, 'draft saved')
other.post('profile.php', {'paper_id': draft_id})
check('>Draft</span>' in guest.request('profile.php')[2], 'other user cannot submit draft')
guest.post('profile.php', {'paper_id': draft_id})
check(draft_title in admin.request('admin_index.php')[2], 'owner submits draft for review')
admin.post('admin_index.php', {'action': 'paper', 'id': draft_id, 'decision': 'rejected'})
check('Rejected' in guest.request('profile.php')[2], 'paper rejection persists')

title = 'Integration Project ' + suffix
profile = guest.post('Project.php', {'title': title, 'description': 'Project description', 'status': 'Recruiting'})[2]
project_id = re.search(r'project_details.php\?id=(\d+)', profile)[1]
check(other.request('project_details.php?id=' + project_id)[0] == 404, 'pending project private')
admin.post('admin_index.php', {'action': 'project', 'id': project_id, 'decision': 'approved'})
check(title in other.request('Project.php')[2], 'approved project listed')
details = 'project_details.php?id=' + project_id
other.post(details, {'action': 'join'})
check('Other Student' in guest.request(details)[2], 'project membership persists')
check(other.post(details, {'action': 'status', 'status': 'Completed'})[0] == 403, 'non-owner cannot change project status')
guest.post(details, {'action': 'status', 'status': 'Completed'})
check('Completed' in other.request(details)[2], 'owner updates status')
other.post(details, {'action': 'leave'})
check('Members (0)' in guest.request(details)[2], 'leave project persists')

title = 'Integration Discussion ' + suffix
status, url, text, _ = guest.post('Community_Forum.php', {'title': title, 'category': 'Tools & Software', 'description': 'Test discussion body'})
check('discussion.php?id=' in url and title in text, 'discussion saved')
discussion_url = url[len(BASE):]
check(title in other.request('tools_software.php')[2], 'forum category filter')
check(title not in other.request('paper_reviews.php')[2], 'other category excludes discussion')
other.post(discussion_url, {'body': '<b>My reply</b>'})
check('&lt;b&gt;My reply&lt;/b&gt;' in guest.request(discussion_url)[2], 'reply persists and is escaped')
check('replied to your discussion' in guest.request('notification.php')[2], 'reply notification created')
check('0 unread' in guest.post('notification.php', {})[2], 'notifications marked read')

settings = guest.post('setting.php', {'action': 'profile', 'fullName': 'Updated Student', 'department': 'EEE', 'bio': 'My research bio'})[2]
check('My research bio' in guest.request('profile.php')[2], 'profile changes persist')
check('current password is incorrect' in guest.post('setting.php', {'action': 'password', 'current_password': 'wrong', 'password': PASSWORD + 'x'})[2], 'password change requires current password')
second_session = Browser()
second_session.login(email)
changed_password = PASSWORD + '-changed'
check(guest.post('setting.php', {'action': 'password', 'current_password': PASSWORD, 'password': changed_password})[1].endswith('signIn.php'), 'password change succeeds')
check(second_session.request('profile.php')[1].endswith('signIn.php'), 'password change revokes other sessions')
check(guest.login(email, changed_password)[1].endswith('User_dashboard.php'), 'changed password works')

resetter = Browser()
resetter.post('forgot_password.php', {'email': email})
reset_page = admin.post('admin_index.php', {'action': 'reset', 'id': user_id})[2]
reset_url = re.search(r'href="(reset_password.php\?token=[a-f0-9]+)"', reset_page)[1]
new_password = PASSWORD + '-reset'
check(resetter.post(reset_url, {'password': new_password})[1].endswith('signIn.php'), 'admin-issued password reset works')
check(resetter.request(reset_url)[0] == 400, 'reset token cannot be reused')
check(guest.request('profile.php')[1].endswith('signIn.php'), 'password reset revokes old sessions')
check(guest.login(email, new_password)[1].endswith('User_dashboard.php'), 'new password works')
admin.post('admin_index.php', {'action': 'user', 'id': user_id, 'active': '0'})
check(guest.request('profile.php')[1].endswith('signIn.php'), 'disabled account loses access')
admin.post('admin_index.php', {'action': 'user', 'id': user_id, 'active': '1'})
check(guest.login(email, new_password)[1].endswith('User_dashboard.php'), 're-enabled account can sign in')
csrf = guest.token('profile.php')
check(guest.request('logout.php', {'csrf': csrf})[1].endswith('signIn.php'), 'logout redirects')
check(guest.request('profile.php')[1].endswith('signIn.php'), 'logout clears access')
for _ in range(5):
    guest.login(email, 'invalid')
check('Too many attempts' in guest.login(email, new_password)[2], 'login rate limit works')
print(f'\n{passed} checks passed. Test records remain in the disposable database.')
