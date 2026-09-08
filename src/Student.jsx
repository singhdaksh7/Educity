import { useCallback, useEffect, useState } from 'react';
import { Link, Navigate, Route, Routes, useNavigate, useParams } from 'react-router-dom';
import { api, studentAuth as auth } from './api';

const message = (e) => Object.values(e.errors || {}).flat().join(' ') || e.message;
const Button = ({ children, className = '', ...props }) => <button {...props} className={`rounded bg-blue-950 px-4 py-2 text-white disabled:opacity-50 ${className}`}>{children}</button>;
const Field = ({ label, children }) => <label className="mb-4 block text-sm font-medium text-slate-700">{label}{children}</label>;
const Input = (props) => <input {...props} className="mt-1 w-full rounded border border-slate-300 p-2 focus:border-blue-700 focus:outline focus:outline-2 focus:outline-blue-700" />;
const Card = ({ children, className = '' }) => <section className={`rounded bg-white p-5 shadow ${className}`}>{children}</section>;
const statusStyles = { submitted: 'bg-slate-200 text-slate-800', under_review: 'bg-amber-100 text-amber-800', contacted: 'bg-blue-100 text-blue-800', accepted: 'bg-green-100 text-green-800', rejected: 'bg-red-100 text-red-800', withdrawn: 'bg-slate-100 text-slate-500', new: 'bg-slate-200 text-slate-800', in_progress: 'bg-amber-100 text-amber-800', closed: 'bg-slate-100 text-slate-500' };
const StatusBadge = ({ status }) => <span className={`rounded-full px-3 py-1 text-xs font-semibold capitalize ${statusStyles[status] || 'bg-slate-100 text-slate-700'}`}>{(status || '').replaceAll('_', ' ')}</span>;

function AuthShell({ title, children }) {
  return (
    <main className="grid min-h-screen place-items-center bg-slate-100 p-4">
      <div className="w-full max-w-md rounded bg-white p-8 shadow">
        <Link to="/" className="mb-6 block text-sm text-blue-800 underline">&larr; Back to Educity</Link>
        <h1 className="mb-5 text-2xl font-bold text-blue-950">{title}</h1>
        {children}
      </div>
    </main>
  );
}

function Register() {
  const [form, setForm] = useState({ name: '', email: '', phone: '', password: '', password_confirmation: '', website: '' });
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);
  const nav = useNavigate();
  const submit = async (e) => {
    e.preventDefault(); setBusy(true); setError('');
    try {
      auth.set((await api('/student/auth/register', { method: 'POST', body: JSON.stringify(form) })).data);
      nav('/student', { replace: true });
    } catch (x) { setError(message(x)); } finally { setBusy(false); }
  };
  return (
    <AuthShell title="Create your student account">
      <form onSubmit={submit} noValidate>
        {error && <p role="alert" className="mb-3 text-red-700">{error}</p>}
        <Field label="Full name"><Input required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} /></Field>
        <Field label="Email"><Input required type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} /></Field>
        <Field label="Phone (optional)"><Input type="tel" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} /></Field>
        <Field label="Password"><Input required type="password" minLength={8} value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} /></Field>
        <Field label="Confirm password"><Input required type="password" value={form.password_confirmation} onChange={(e) => setForm({ ...form, password_confirmation: e.target.value })} /></Field>
        <input type="text" tabIndex={-1} autoComplete="off" value={form.website} onChange={(e) => setForm({ ...form, website: e.target.value })} className="hidden" aria-hidden="true" />
        <Button disabled={busy} className="w-full">{busy ? 'Creating account…' : 'Create account'}</Button>
        <p className="mt-4 text-sm">Already have an account? <Link className="text-blue-800 underline" to="/student/login">Sign in</Link></p>
      </form>
    </AuthShell>
  );
}

function Login() {
  const [form, setForm] = useState({ email: '', password: '' });
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);
  const nav = useNavigate();
  const submit = async (e) => {
    e.preventDefault(); setBusy(true); setError('');
    try {
      auth.set((await api('/student/auth/login', { method: 'POST', body: JSON.stringify(form) })).data);
      nav('/student', { replace: true });
    } catch (x) { setError(message(x)); } finally { setBusy(false); }
  };
  return (
    <AuthShell title="Student sign in">
      <form onSubmit={submit} noValidate>
        {error && <p role="alert" className="mb-3 text-red-700">{error}</p>}
        <Field label="Email"><Input required type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} /></Field>
        <Field label="Password"><Input required type="password" value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} /></Field>
        <Button disabled={busy} className="w-full">{busy ? 'Signing in…' : 'Sign in'}</Button>
        <div className="mt-4 flex justify-between text-sm">
          <Link className="text-blue-800 underline" to="/student/forgot-password">Forgot password?</Link>
          <Link className="text-blue-800 underline" to="/student/register">Create account</Link>
        </div>
      </form>
    </AuthShell>
  );
}

function Forgot() {
  const [email, setEmail] = useState('');
  const [notice, setNotice] = useState('');
  const [busy, setBusy] = useState(false);
  const submit = async (e) => {
    e.preventDefault(); setBusy(true);
    try { setNotice((await api('/student/auth/forgot-password', { method: 'POST', body: JSON.stringify({ email }) })).message); }
    catch (x) { setNotice(message(x)); } finally { setBusy(false); }
  };
  return (
    <AuthShell title="Reset your password">
      <form onSubmit={submit} noValidate>
        <Field label="Email"><Input required type="email" value={email} onChange={(e) => setEmail(e.target.value)} /></Field>
        <Button disabled={busy} className="w-full">{busy ? 'Sending…' : 'Send reset link'}</Button>
        {notice && <p role="status" className="mt-3 text-sm text-slate-700">{notice}</p>}
        <p className="mt-4 text-sm"><Link className="text-blue-800 underline" to="/student/login">Back to sign in</Link></p>
      </form>
    </AuthShell>
  );
}

function Reset() {
  const [form, setForm] = useState({ token: new URLSearchParams(window.location.search).get('token') || '', email: new URLSearchParams(window.location.search).get('email') || '', password: '', password_confirmation: '' });
  const [notice, setNotice] = useState('');
  const [busy, setBusy] = useState(false);
  const nav = useNavigate();
  const submit = async (e) => {
    e.preventDefault(); setBusy(true);
    try {
      await api('/student/auth/reset-password', { method: 'POST', body: JSON.stringify(form) });
      setNotice('Password reset. Redirecting to sign in…');
      setTimeout(() => nav('/student/login', { replace: true }), 1200);
    } catch (x) { setNotice(message(x)); } finally { setBusy(false); }
  };
  return (
    <AuthShell title="Choose a new password">
      <form onSubmit={submit} noValidate>
        <Field label="Email"><Input required type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} /></Field>
        <Field label="Reset token"><Input required value={form.token} onChange={(e) => setForm({ ...form, token: e.target.value })} /></Field>
        <Field label="New password"><Input required type="password" minLength={8} value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} /></Field>
        <Field label="Confirm new password"><Input required type="password" value={form.password_confirmation} onChange={(e) => setForm({ ...form, password_confirmation: e.target.value })} /></Field>
        <Button disabled={busy} className="w-full">{busy ? 'Saving…' : 'Reset password'}</Button>
        {notice && <p role="status" className="mt-3 text-sm text-slate-700">{notice}</p>}
      </form>
    </AuthShell>
  );
}

function Shell({ children }) {
  const nav = useNavigate();
  const [menuOpen, setMenuOpen] = useState(false);
  const user = auth.user() || {};
  const links = [['', 'Dashboard'], ['applications', 'Applications'], ['enquiries', 'Enquiries'], ['profile', 'Profile'], ['password', 'Password']];
  const logout = async () => {
    try { await api('/student/auth/logout', { method: 'POST' }); } catch { /* Clear local session data even if the backend is unreachable. */ }
    auth.clear();
    nav('/student/login', { replace: true });
  };
  return (
    <div className="min-h-screen bg-slate-100">
      <header className="flex items-center justify-between gap-3 bg-blue-950 p-4 text-white">
        <Link className="font-bold" to="/student">Educity Student Portal</Link>
        <button className="md:hidden" aria-label="Toggle menu" aria-expanded={menuOpen} onClick={() => setMenuOpen((v) => !v)}>☰</button>
        <span className="hidden items-center gap-3 md:flex">{user.name} <button className="underline" onClick={logout}>Logout</button></span>
      </header>
      <div className="md:flex">
        <nav className={`${menuOpen ? 'flex' : 'hidden'} flex-col gap-3 bg-white p-4 md:flex md:min-h-[calc(100vh-64px)] md:w-56`}>
          {links.map(([to, label]) => <Link key={to} className="hover:text-blue-800" to={`/student/${to}`} onClick={() => setMenuOpen(false)}>{label}</Link>)}
          <button className="text-left text-red-700 underline md:hidden" onClick={logout}>Logout</button>
        </nav>
        <main className="min-w-0 flex-1 p-4 md:p-6">{children}</main>
      </div>
    </div>
  );
}

function Guard({ children }) {
  const [ok, setOk] = useState(() => (auth.token() ? undefined : false));
  useEffect(() => {
    if (auth.token()) {
      api('/student/auth/me').then((r) => { auth.set({ token: auth.token(), user: r.data }); setOk(true); }).catch(() => setOk(false));
    }
  }, []);
  if (ok === undefined) return <p className="p-6">Checking session…</p>;
  return ok ? <Shell>{children}</Shell> : <Navigate to="/student/login" replace />;
}

function Dashboard() {
  const [data, setData] = useState();
  const [error, setError] = useState('');
  useEffect(() => { api('/student/dashboard').then((r) => setData(r.data)).catch((e) => setError(message(e))); }, []);
  if (error) return <p role="alert" className="text-red-700">{error}</p>;
  if (!data) return <p>Loading dashboard…</p>;
  const cards = { 'Total applications': data.total_applications, 'Total enquiries': data.total_enquiries };
  return (
    <>
      <h1 className="mb-5 text-2xl font-bold">Welcome, {data.profile?.name}</h1>
      <div className="mb-6 grid gap-4 sm:grid-cols-2">
        {Object.entries(cards).map(([label, value]) => <Card key={label}><p className="text-slate-500">{label}</p><strong className="text-3xl">{value ?? 0}</strong></Card>)}
      </div>
      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <h2 className="mb-3 text-lg font-semibold">Recent applications</h2>
          {data.recent_applications?.length ? <ul className="space-y-2">{data.recent_applications.map((a) => <li key={a.id} className="flex items-center justify-between border-b pb-2 text-sm"><Link className="text-blue-800 underline" to={`/student/applications/${a.id}`}>{a.program?.title || 'Application'}</Link><StatusBadge status={a.status} /></li>)}</ul> : <p className="text-sm text-slate-500">No applications yet. <Link className="text-blue-800 underline" to="/student/applications/new">Apply now</Link>.</p>}
        </Card>
        <Card>
          <h2 className="mb-3 text-lg font-semibold">Recent enquiries</h2>
          {data.recent_enquiries?.length ? <ul className="space-y-2">{data.recent_enquiries.map((e) => <li key={e.id} className="flex items-center justify-between border-b pb-2 text-sm"><span>{e.subject || 'Enquiry'}</span><StatusBadge status={e.status} /></li>)}</ul> : <p className="text-sm text-slate-500">No enquiries yet. <Link className="text-blue-800 underline" to="/student/enquiries/new">Ask a question</Link>.</p>}
        </Card>
      </div>
      {data.featured_programmes?.length > 0 && (
        <Card className="mt-6">
          <h2 className="mb-3 text-lg font-semibold">Featured programmes</h2>
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">{data.featured_programmes.map((p) => <div key={p.id} className="rounded border p-3"><p className="font-semibold">{p.title}</p><p className="text-sm text-slate-500">{p.degree_type}</p></div>)}</div>
        </Card>
      )}
    </>
  );
}

function Profile() {
  const [form, setForm] = useState(() => auth.user() || {});
  const [notice, setNotice] = useState('');
  const submit = async (e) => {
    e.preventDefault();
    try {
      const r = await api('/student/auth/profile', { method: 'PATCH', body: JSON.stringify({ name: form.name, email: form.email, phone: form.phone }) });
      auth.set({ token: auth.token(), user: r.data });
      setNotice('Profile updated.');
    } catch (x) { setNotice(message(x)); }
  };
  return (
    <form onSubmit={submit} className="max-w-lg" noValidate>
      <h1 className="mb-4 text-2xl font-bold">My profile</h1>
      <Card>
        <Field label="Full name"><Input required value={form.name || ''} onChange={(e) => setForm({ ...form, name: e.target.value })} /></Field>
        <Field label="Email"><Input required type="email" value={form.email || ''} onChange={(e) => setForm({ ...form, email: e.target.value })} /></Field>
        <Field label="Phone"><Input value={form.phone || ''} onChange={(e) => setForm({ ...form, phone: e.target.value })} /></Field>
        <Button>Save changes</Button>
        {notice && <p role="status" className="mt-3 text-sm text-slate-700">{notice}</p>}
      </Card>
    </form>
  );
}

function PasswordForm() {
  const [form, setForm] = useState({ current_password: '', password: '', password_confirmation: '' });
  const [notice, setNotice] = useState('');
  const submit = async (e) => {
    e.preventDefault();
    try { setNotice((await api('/student/auth/password', { method: 'PATCH', body: JSON.stringify(form) })).message); setForm({ current_password: '', password: '', password_confirmation: '' }); }
    catch (x) { setNotice(message(x)); }
  };
  return (
    <form onSubmit={submit} className="max-w-lg" noValidate>
      <h1 className="mb-4 text-2xl font-bold">Change password</h1>
      <Card>
        <Field label="Current password"><Input required type="password" value={form.current_password} onChange={(e) => setForm({ ...form, current_password: e.target.value })} /></Field>
        <Field label="New password"><Input required type="password" minLength={8} value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} /></Field>
        <Field label="Confirm new password"><Input required type="password" value={form.password_confirmation} onChange={(e) => setForm({ ...form, password_confirmation: e.target.value })} /></Field>
        <Button>Update password</Button>
        {notice && <p role="status" className="mt-3 text-sm text-slate-700">{notice}</p>}
      </Card>
    </form>
  );
}

function Applications() {
  const [result, setResult] = useState();
  const [error, setError] = useState('');
  useEffect(() => { api('/student/applications').then((r) => setResult(r.data)).catch((e) => setError(message(e))); }, []);
  return (
    <>
      <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
        <h1 className="text-2xl font-bold">My applications</h1>
        <Link className="rounded bg-blue-950 px-4 py-2 text-white" to="/student/applications/new">New application</Link>
      </div>
      {error && <p role="alert" className="text-red-700">{error}</p>}
      {!result ? <p>Loading…</p> : result.data.length ? (
        <div className="overflow-x-auto rounded bg-white shadow">
          <table className="w-full text-left">
            <thead><tr><th className="p-3">Application #</th><th>Programme</th><th>Status</th><th className="p-3">Submitted</th><th /></tr></thead>
            <tbody>{result.data.map((a) => <tr className="border-t" key={a.id}><td className="p-3">{a.application_number}</td><td>{a.program?.title}</td><td><StatusBadge status={a.status} /></td><td className="p-3">{new Date(a.created_at).toLocaleDateString()}</td><td className="p-3"><Link className="text-blue-800 underline" to={`/student/applications/${a.id}`}>View</Link></td></tr>)}</tbody>
          </table>
        </div>
      ) : <p className="text-slate-500">You haven't submitted any applications yet.</p>}
    </>
  );
}

function ApplicationNew() {
  const [programs, setPrograms] = useState([]);
  const [form, setForm] = useState({ full_name: '', email: '', phone: '', program_id: '', previous_qualification: '', message: '', website: '' });
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [busy, setBusy] = useState(false);
  useEffect(() => { api('/programs').then((r) => setPrograms(r.data)).catch(() => {}); }, []);
  useEffect(() => { const u = auth.user(); if (u) setForm((f) => ({ ...f, full_name: f.full_name || u.name, email: f.email || u.email, phone: f.phone || u.phone || '' })); }, []);
  const submit = async (e) => {
    e.preventDefault(); setBusy(true); setError('');
    try {
      const r = await api('/student/applications', { method: 'POST', body: JSON.stringify(form) });
      setSuccess(`Application submitted (${r.data.application_number}).`);
    } catch (x) { setError(message(x)); } finally { setBusy(false); }
  };
  if (success) return <Card className="max-w-lg"><p role="status" className="text-green-700">{success}</p><Link className="mt-4 inline-block text-blue-800 underline" to="/student/applications">View my applications</Link></Card>;
  return (
    <form onSubmit={submit} className="max-w-lg" noValidate>
      <h1 className="mb-4 text-2xl font-bold">New application</h1>
      <Card>
        {error && <p role="alert" className="mb-3 text-red-700">{error}</p>}
        <Field label="Full name"><Input required value={form.full_name} onChange={(e) => setForm({ ...form, full_name: e.target.value })} /></Field>
        <Field label="Email"><Input required type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} /></Field>
        <Field label="Phone"><Input required value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} /></Field>
        <Field label="Programme">
          <select required className="mt-1 w-full rounded border border-slate-300 p-2" value={form.program_id} onChange={(e) => setForm({ ...form, program_id: e.target.value })}>
            <option value="">Select a programme</option>
            {programs.map((p) => <option key={p.id} value={p.id}>{p.title}</option>)}
          </select>
        </Field>
        <Field label="Previous qualification (optional)"><Input value={form.previous_qualification} onChange={(e) => setForm({ ...form, previous_qualification: e.target.value })} /></Field>
        <Field label="Message (optional)"><textarea className="mt-1 w-full rounded border border-slate-300 p-2" value={form.message} onChange={(e) => setForm({ ...form, message: e.target.value })} /></Field>
        <input type="text" tabIndex={-1} autoComplete="off" value={form.website} onChange={(e) => setForm({ ...form, website: e.target.value })} className="hidden" aria-hidden="true" />
        <Button disabled={busy}>{busy ? 'Submitting…' : 'Submit application'}</Button>
      </Card>
    </form>
  );
}

function ApplicationDetail() {
  const { id } = useParams();
  const [item, setItem] = useState();
  const [error, setError] = useState('');
  const [notice, setNotice] = useState('');
  const load = useCallback(() => { api(`/student/applications/${id}`).then((r) => setItem(r.data)).catch((e) => setError(message(e))); }, [id]);
  useEffect(() => { load(); }, [load]);
  const withdraw = async () => {
    if (!confirm('Withdraw this application?')) return;
    try { await api(`/student/applications/${id}/withdraw`, { method: 'PATCH' }); setNotice('Application withdrawn.'); load(); }
    catch (x) { setNotice(message(x)); }
  };
  if (error) return <p role="alert" className="text-red-700">{error}</p>;
  if (!item) return <p>Loading…</p>;
  const canWithdraw = ['submitted', 'under_review'].includes(item.status);
  return (
    <Card className="max-w-2xl">
      <div className="mb-4 flex items-center justify-between"><h1 className="text-2xl font-bold">{item.application_number}</h1><StatusBadge status={item.status} /></div>
      <p><b>Programme:</b> {item.program?.title}</p>
      <p><b>Full name:</b> {item.full_name}</p>
      <p><b>Email:</b> {item.email}</p>
      <p><b>Phone:</b> {item.phone}</p>
      {item.message && <p><b>Message:</b> {item.message}</p>}
      <p className="text-sm text-slate-500">Submitted {new Date(item.created_at).toLocaleString()}</p>
      {canWithdraw && <Button className="mt-4 bg-red-700" onClick={withdraw}>Withdraw application</Button>}
      {notice && <p role="status" className="mt-3 text-sm">{notice}</p>}
    </Card>
  );
}

function Enquiries() {
  const [result, setResult] = useState();
  const [error, setError] = useState('');
  useEffect(() => { api('/student/enquiries').then((r) => setResult(r.data)).catch((e) => setError(message(e))); }, []);
  return (
    <>
      <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
        <h1 className="text-2xl font-bold">My enquiries</h1>
        <Link className="rounded bg-blue-950 px-4 py-2 text-white" to="/student/enquiries/new">New enquiry</Link>
      </div>
      {error && <p role="alert" className="text-red-700">{error}</p>}
      {!result ? <p>Loading…</p> : result.data.length ? (
        <div className="overflow-x-auto rounded bg-white shadow">
          <table className="w-full text-left">
            <thead><tr><th className="p-3">Subject</th><th>Status</th><th className="p-3">Sent</th></tr></thead>
            <tbody>{result.data.map((e) => <tr className="border-t" key={e.id}><td className="p-3">{e.subject || 'General enquiry'}</td><td><StatusBadge status={e.status} /></td><td className="p-3">{new Date(e.created_at).toLocaleDateString()}</td></tr>)}</tbody>
          </table>
        </div>
      ) : <p className="text-slate-500">You haven't sent any enquiries yet.</p>}
    </>
  );
}

function EnquiryNew() {
  const [form, setForm] = useState({ name: '', email: '', phone: '', subject: '', message: '', website: '' });
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);
  const [busy, setBusy] = useState(false);
  useEffect(() => { const u = auth.user(); if (u) setForm((f) => ({ ...f, name: f.name || u.name, email: f.email || u.email, phone: f.phone || u.phone || '' })); }, []);
  const submit = async (e) => {
    e.preventDefault(); setBusy(true); setError('');
    try { await api('/student/enquiries', { method: 'POST', body: JSON.stringify(form) }); setSuccess(true); }
    catch (x) { setError(message(x)); } finally { setBusy(false); }
  };
  if (success) return <Card className="max-w-lg"><p role="status" className="text-green-700">Your enquiry has been received. We'll get back to you shortly.</p><Link className="mt-4 inline-block text-blue-800 underline" to="/student/enquiries">View my enquiries</Link></Card>;
  return (
    <form onSubmit={submit} className="max-w-lg" noValidate>
      <h1 className="mb-4 text-2xl font-bold">New enquiry</h1>
      <Card>
        {error && <p role="alert" className="mb-3 text-red-700">{error}</p>}
        <Field label="Subject (optional)"><Input value={form.subject} onChange={(e) => setForm({ ...form, subject: e.target.value })} /></Field>
        <Field label="Message"><textarea required className="mt-1 w-full rounded border border-slate-300 p-2" value={form.message} onChange={(e) => setForm({ ...form, message: e.target.value })} /></Field>
        <input type="text" tabIndex={-1} autoComplete="off" value={form.website} onChange={(e) => setForm({ ...form, website: e.target.value })} className="hidden" aria-hidden="true" />
        <Button disabled={busy}>{busy ? 'Sending…' : 'Send enquiry'}</Button>
      </Card>
    </form>
  );
}

function Student() {
  return (
    <Routes>
      <Route path="register" element={<Register />} />
      <Route path="login" element={<Login />} />
      <Route path="forgot-password" element={<Forgot />} />
      <Route path="reset-password" element={<Reset />} />
      <Route path="" element={<Guard><Dashboard /></Guard>} />
      <Route path="profile" element={<Guard><Profile /></Guard>} />
      <Route path="password" element={<Guard><PasswordForm /></Guard>} />
      <Route path="applications" element={<Guard><Applications /></Guard>} />
      <Route path="applications/new" element={<Guard><ApplicationNew /></Guard>} />
      <Route path="applications/:id" element={<Guard><ApplicationDetail /></Guard>} />
      <Route path="enquiries" element={<Guard><Enquiries /></Guard>} />
      <Route path="enquiries/new" element={<Guard><EnquiryNew /></Guard>} />
      <Route path="*" element={<Navigate to="/student" replace />} />
    </Routes>
  );
}

export default Student;
