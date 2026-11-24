import { useCallback, useEffect, useState } from 'react';
import { api } from '../api.js';
import Toast from '../components/Toast.jsx';
import Button from '../components/Button.jsx';

const EMPTY = { title: '', author: '', isbn: '', category: '', stock: 1 };

export default function OperatorBooks({ onChange }) {
  const [books, setBooks] = useState([]);
  const [form, setForm] = useState(EMPTY);
  const [editingId, setEditingId] = useState(null);
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await api('/books');
      setBooks(data.books);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    Promise.resolve().then(load);
  }, [load]);

  function edit(book) {
    setEditingId(book.id);
    setForm({
      title: book.title,
      author: book.author,
      isbn: book.isbn,
      category: book.category,
      stock: book.stock,
    });
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
      const body = { ...form, stock: Number(form.stock) };
      if (editingId) {
        await api(`/books/${editingId}`, { method: 'PUT', body });
        setMessage('Libri u përditësua.');
      } else {
        await api('/books', { method: 'POST', body });
        setMessage('Libri u shtua.');
      }
      reset();
      await load();
      onChange?.();
    } catch (err) {
      setError(err.message);
    } finally {
      setBusy(false);
    }
  }

  async function remove(id) {
    if (!window.confirm('Fshi këtë libër?')) return;
    setError('');
    try {
      await api(`/books/${id}`, { method: 'DELETE' });
      setMessage('Libri u fshi.');
      await load();
      onChange?.();
    } catch (err) {
      setError(err.message);
    }
  }

  return (
    <div className="card">
      <h2>Librat</h2>
      {loading && (
        <p className="muted" role="status">
          Duke ngarkuar…
        </p>
      )}

      <form
        onSubmit={save}
        style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(3, 1fr)',
          gap: 8,
          marginBottom: 16,
        }}
      >
        <input
          className="input"
          aria-label="Titulli"
          placeholder="Titulli"
          value={form.title}
          onChange={(e) => setForm({ ...form, title: e.target.value })}
          required
        />
        <input
          className="input"
          aria-label="Autori"
          placeholder="Autori"
          value={form.author}
          onChange={(e) => setForm({ ...form, author: e.target.value })}
          required
        />
        <input
          className="input"
          aria-label="ISBN"
          placeholder="ISBN"
          value={form.isbn}
          onChange={(e) => setForm({ ...form, isbn: e.target.value })}
          required
        />
        <input
          className="input"
          aria-label="Kategoria"
          placeholder="Kategoria"
          value={form.category}
          onChange={(e) => setForm({ ...form, category: e.target.value })}
          required
        />
        <input
          className="input"
          type="number"
          min="0"
          aria-label="Stoku"
          placeholder="Stoku"
          value={form.stock}
          onChange={(e) => setForm({ ...form, stock: e.target.value })}
          required
        />
        <div style={{ display: 'flex', gap: 8 }}>
          <Button type="submit" loading={busy}>
            {editingId ? 'Ruaj' : 'Shto'}
          </Button>
          {editingId && (
            <Button type="button" onClick={reset}>
              Anulo
            </Button>
          )}
        </div>
      </form>

      <table>
        <thead>
          <tr>
            <th>Titulli</th>
            <th>Autori</th>
            <th>ISBN</th>
            <th>Stoku</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          {books.map((b) => (
            <tr key={b.id}>
              <td>{b.title}</td>
              <td>{b.author}</td>
              <td>{b.isbn}</td>
              <td>
                <span
                  className={`badge ${b.stock === 0 ? 'badge-bad' : b.stock <= 2 ? 'badge-warn' : 'badge-ok'}`}
                >
                  {b.stock}
                </span>
              </td>
              <td style={{ display: 'flex', gap: 6 }}>
                <Button onClick={() => edit(b)}>Ndrysho</Button>
                <Button className="btn btn-danger" onClick={() => remove(b.id)}>
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
