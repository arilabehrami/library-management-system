import { useCallback, useEffect, useState } from 'react';
import { api } from '../api.js';
import Toast from '../components/Toast.jsx';
import Button from '../components/Button.jsx';

const EMPTY = { name: '', email: '', password: '', role: 'student' };

export default function AdminUsers() {
  const [users, setUsers] = useState([]);
  const [form, setForm] = useState(EMPTY);
  const [editingId, setEditingId] = useState(null);
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await api('/admin/users');
      setUsers(data.users);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    Promise.resolve().then(load);
  }, [load]);

  function edit(u) {
    setEditingId(u.id);
    setForm({ name: u.name, email: u.email, password: '', role: u.role });
  }

  function reset() {
    setEditingId(null);
    setForm(EMPTY);
  }

  async function save(e) {
    e.preventDefault();
    setBusy(true);
    setError('');
    try {
      if (editingId) {
        const body = { name: form.name, email: form.email, role: form.role };
        if (form.password) body.password = form.password;
        await api(`/admin/users/${editingId}`, { method: 'PUT', body });
        setMessage('Llogaria u përditësua.');
      } else {
        await api('/admin/users', { method: 'POST', body: form });
        setMessage('Llogaria u krijua.');
      }
      reset();
      await load();
    } catch (err) {
      setError(err.message);
    } finally {
      setBusy(false);
    }
  }

  async function remove(u) {
    if (!window.confirm(`Fshi llogarinë e ${u.name}?`)) return;
    setError('');
    try {
      await api(`/admin/users/${u.id}`, { method: 'DELETE' });
      setMessage('Llogaria u fshi.');
      await load();
    } catch (err) {
      setError(err.message);
    }
  }

  return (
    <div className="card">
      <h2>Llogaritë</h2>
      {loading && (
        <p className="muted" role="status">
          Duke ngarkuar…
        </p>
      )}

      <form
        onSubmit={save}
        style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(2, 1fr)',
          gap: 8,
          marginBottom: 16,
        }}
      >
        <input
          className="input"
          aria-label="Emri"
          placeholder="Emri"
          value={form.name}
          onChange={(e) => setForm({ ...form, name: e.target.value })}
          required
        />
        <input
          className="input"
          type="email"
          aria-label="Email"
          placeholder="Email"
          value={form.email}
          onChange={(e) => setForm({ ...form, email: e.target.value })}
          required
        />
        <input
          className="input"
          aria-label="Fjalëkalimi"
          type="password"
          placeholder={
            editingId ? 'Fjalëkalim i ri (opsional)' : 'Fjalëkalimi (min. 8)'
          }
          value={form.password}
          onChange={(e) => setForm({ ...form, password: e.target.value })}
          required={!editingId}
        />
        <select
          aria-label="Roli"
          className="input"
          value={form.role}
          onChange={(e) => setForm({ ...form, role: e.target.value })}
        >
          <option value="student">Student</option>
          <option value="operator">Operator</option>
        </select>
        <div style={{ display: 'flex', gap: 8 }}>
          <Button type="submit" loading={busy}>
            {editingId ? 'Ruaj' : 'Krijo'}
          </Button>
          {editingId && (
            <Button type="button" className="btn-secondary" onClick={reset}>
              Anulo
            </Button>
          )}
        </div>
      </form>

      <table>
        <thead>
          <tr>
            <th>Emri</th>
            <th>Email</th>
            <th>Roli</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          {users
            .filter((u) => u.role !== 'super_admin')
            .map((u) => (
              <tr key={u.id}>
                <td>{u.name}</td>
                <td>{u.email}</td>
                <td>
                  <span className="badge badge-ok">{u.role}</span>
                </td>
                <td style={{ display: 'flex', gap: 6 }}>
                  <Button onClick={() => edit(u)}>Ndrysho</Button>
                  <Button className="btn-danger" onClick={() => remove(u)}>
                    Fshi
                  </Button>
                </td>
              </tr>
            ))}
        </tbody>
      </table>

      <Toast message={message} type="ok" onClose={() => setMessage('')} />
      <Toast message={error} type="error" onClose={() => setError('')} />
    </div>
  );
}
