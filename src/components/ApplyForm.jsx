import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api, studentAuth } from '../api';

const message = (e) => Object.values(e.errors || {}).flat().join(' ') || e.message;

function ApplyForm() {
  const [programs, setPrograms] = useState([]);
  const isStudent = !!studentAuth.token();
  const student = studentAuth.user();
  const [form, setForm] = useState({
    full_name: student?.name || '',
    email: student?.email || '',
    phone: student?.phone || '',
    program_id: '',
    previous_qualification: '',
    message: '',
    website: '',
  });
  const [consent, setConsent] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    api('/programs').then((r) => setPrograms(r.data)).catch(() => setError('Unable to load programmes right now. Please try again shortly.'));
  }, []);

  const submit = async (e) => {
    e.preventDefault();
    if (!consent) { setError('Please confirm you consent to Educity contacting you about this application.'); return; }
    setBusy(true); setError('');
    try {
      const path = isStudent ? '/student/applications' : '/admission-applications';
      const r = await api(path, { method: 'POST', body: JSON.stringify(form) });
      setSuccess(r.data?.application_number ? `Thank you — your application number is ${r.data.application_number}.` : 'Thank you — your application has been received.');
    } catch (x) {
      setError(x.status === 429 ? 'You have submitted recently. Please wait a moment before trying again.' : message(x));
    } finally { setBusy(false); }
  };

  return (
    <main id="apply" className="mx-auto max-w-2xl px-4 py-24">
      <Link to="/" className="mb-6 inline-block text-sm text-blue-800 underline">&larr; Back to Educity</Link>
      <h1 className="mb-2 text-3xl font-bold text-blue-950">Apply for admission</h1>
      <p className="mb-6 text-slate-600">{isStudent ? `Applying as ${student.name}.` : <>Already have an account? <Link className="text-blue-800 underline" to="/student/login">Sign in</Link> to track your applications, or continue as a guest below.</>}</p>

      {success ? (
        <div role="status" className="rounded bg-green-50 p-6 text-green-800 shadow">
          <p className="text-lg font-semibold">Application submitted</p>
          <p className="mt-2">{success}</p>
          {isStudent && <Link className="mt-4 inline-block text-blue-800 underline" to="/student/applications">View my applications</Link>}
        </div>
      ) : (
        <form onSubmit={submit} noValidate className="rounded bg-white p-6 shadow">
          {error && <p role="alert" className="mb-4 text-red-700">{error}</p>}

          <label className="mb-4 block text-sm font-medium">Full name
            <input required value={form.full_name} onChange={(e) => setForm({ ...form, full_name: e.target.value })} className="mt-1 w-full rounded border border-slate-300 p-2 focus:border-blue-700 focus:outline focus:outline-2 focus:outline-blue-700" />
          </label>
          <label className="mb-4 block text-sm font-medium">Email
            <input required type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} className="mt-1 w-full rounded border border-slate-300 p-2 focus:border-blue-700 focus:outline focus:outline-2 focus:outline-blue-700" />
          </label>
          <label className="mb-4 block text-sm font-medium">Phone
            <input required value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} className="mt-1 w-full rounded border border-slate-300 p-2 focus:border-blue-700 focus:outline focus:outline-2 focus:outline-blue-700" />
          </label>
          <label className="mb-4 block text-sm font-medium">Programme
            <select required value={form.program_id} onChange={(e) => setForm({ ...form, program_id: e.target.value })} className="mt-1 w-full rounded border border-slate-300 p-2">
              <option value="">Select a programme</option>
              {programs.map((p) => <option key={p.id} value={p.id}>{p.title}</option>)}
            </select>
          </label>
          <label className="mb-4 block text-sm font-medium">Previous qualification (optional)
            <input value={form.previous_qualification} onChange={(e) => setForm({ ...form, previous_qualification: e.target.value })} className="mt-1 w-full rounded border border-slate-300 p-2 focus:border-blue-700 focus:outline focus:outline-2 focus:outline-blue-700" />
          </label>
          <label className="mb-4 block text-sm font-medium">Message (optional)
            <textarea value={form.message} onChange={(e) => setForm({ ...form, message: e.target.value })} className="mt-1 w-full rounded border border-slate-300 p-2 focus:border-blue-700 focus:outline focus:outline-2 focus:outline-blue-700" />
          </label>
          <input type="text" name="website" tabIndex={-1} autoComplete="off" value={form.website} onChange={(e) => setForm({ ...form, website: e.target.value })} className="hidden" aria-hidden="true" />
          <label className="mb-6 flex items-start gap-2 text-sm">
            <input required type="checkbox" checked={consent} onChange={(e) => setConsent(e.target.checked)} className="mt-1" />
            I consent to Educity contacting me about this application.
          </label>
          <button disabled={busy} className="w-full rounded bg-blue-950 px-4 py-3 font-semibold text-white disabled:opacity-50">{busy ? 'Submitting…' : 'Submit application'}</button>
        </form>
      )}
    </main>
  );
}

export default ApplyForm;
