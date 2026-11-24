import { formatDate } from '../formatDate.js';
import { useState } from 'react';
import { api } from '../api.js';
import useData from '../hooks/useData.js';
import Button from '../components/Button.jsx';
import Toast from '../components/Toast.jsx';

export default function StudentCatalog({ refreshKey, onChange }) {
  const [search, setSearch] = useState('');
  const [query, setQuery] = useState('');
  const [category, setCategory] = useState('');
  const [availableOnly, setAvailableOnly] = useState(false);
  const [busy, setBusy] = useState(null);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const books = useData(`/books?q=${encodeURIComponent(query)}`, refreshKey);
  const status = useData('/me/loans', refreshKey);
  const penalties = useData('/me/penalties', refreshKey);
  const policy = status.data?.policy;
  const active = status.data?.loans || [];
  const unpaid = penalties.data?.penalties.some((p) => !p.paid);
  const overdue = Number(policy?.penalty_rate) > 0 && active.some((l) => l.status === 'overdue');
  const limitReached =
    policy && active.length >= Number(policy.borrowing_limit);
  const blocked = unpaid || overdue || limitReached;
  const reason =
    unpaid || overdue
      ? 'Ke një libër të vonuar ose penalitet të papaguar. Përfundo kthimin dhe pagesën për të huazuar përsëri.'
      : limitReached
        ? `Ke arritur limitin prej ${policy.borrowing_limit} librash.`
        : '';
  const all = books.data?.books || [];
  const categories = [...new Set(all.map((b) => b.category))].sort();
  const filtered = all.filter(
    (b) =>
      (!category || b.category === category) && (!availableOnly || b.stock > 0),
  );
  async function borrow(book) {
    if (
      !window.confirm(
        `Huazo “${book.title}” për €${policy.borrow_fee}? Pagesa simulohet për këtë demonstrim.`,
      )
    )
      return;
    setBusy(book.id);
    setError('');
    try {
      const result = await api('/loans', {
        method: 'POST',
        body: { book_id: book.id },
      });
      setMessage(
        `Libri u huazua. Ktheje deri më ${formatDate(result.loan.due_at)}.`,
      );
      onChange();
    } catch (err) {
      setError(err.message);
    } finally {
      setBusy(null);
    }
  }
  return (
    <>
      <div className="stats-grid">
        <div className="stat">
          <span>Huazime aktive</span>
          <strong>
            {status.data
              ? `${active.length} / ${policy?.borrowing_limit}`
              : '—'}
          </strong>
          <small>Libra në llogarinë tënde</small>
        </div>
        <div className="stat">
          <span>Periudha e leximit</span>
          <strong>
            {policy?.borrowing_period || '—'} <em>ditë</em>
          </strong>
          <small>Nga dita e huazimit</small>
        </div>
        <div className="stat">
          <span>Tarifa për libër</span>
          <strong>€{policy?.borrow_fee || '—'}</strong>
          <small>
            Penaliteti: €{policy?.penalty_rate || '—'} / ditë vonesë
          </small>
        </div>
      </div>
      <section className="card">
        <div className="section-heading">
          <div>
            <span className="eyebrow">NË RAFTET TONA</span>
            <h2>Zbulo katalogun</h2>
          </div>
          <span className="muted">{filtered.length} libra</span>
        </div>
        <form
          className="toolbar"
          onSubmit={(e) => {
            e.preventDefault();
            setQuery(search.trim());
            setCategory('');
          }}
        >
          <label className="search-field">
            <span className="sr-only">Kërko libra</span>
            <input
              className="input"
              placeholder="Titull, autor, kategori ose ISBN…"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </label>
          <Button type="submit">Kërko</Button>
          <label>
            <span className="sr-only">Kategoria</span>
            <select
              className="input"
              value={category}
              onChange={(e) => setCategory(e.target.value)}
            >
              <option value="">Çdo kategori</option>
              {categories.map((c) => (
                <option key={c}>{c}</option>
              ))}
            </select>
          </label>
          <label className="checkbox">
            <input
              type="checkbox"
              checked={availableOnly}
              onChange={(e) => setAvailableOnly(e.target.checked)}
            />
            Vetëm në stok
          </label>
        </form>
        {reason && <p className="notice">{reason}</p>}
        {(books.error || status.error || penalties.error) && (
          <p className="error-box" role="alert">
            {books.error || status.error || penalties.error}
          </p>
        )}
        {books.loading ? (
          <p className="empty" role="status">
            Duke kërkuar në rafte…
          </p>
        ) : (
          <div className="book-grid">
            {filtered.map((book, i) => (
              <article className="book-card" key={book.id}>
                <div className={`book-cover cover-${i % 5}`}>
                  <span>{book.category}</span>
                  <h3>{book.title}</h3>
                  <small>{book.author}</small>
                  <div className="cover-line" />
                </div>
                <div className="book-info">
                  <span
                    className={`badge ${book.stock ? 'badge-ok' : 'badge-bad'}`}
                  >
                    {book.stock
                      ? `${book.stock} kopje në stok`
                      : 'I padisponueshëm'}
                  </span>
                  <h3>{book.title}</h3>
                  <p>{book.author}</p>
                  <small>ISBN {book.isbn}</small>
                  <Button
                    loading={busy === book.id}
                    disabled={
                      busy !== null ||
                      !book.stock ||
                      blocked ||
                      !policy ||
                      !penalties.data
                    }
                    onClick={() => borrow(book)}
                  >
                    Huazo · €{policy?.borrow_fee || '—'}
                  </Button>
                </div>
              </article>
            ))}
          </div>
        )}
        {!books.loading && !books.error && !filtered.length && (
          <div className="empty">
            <h3>Nuk u gjet asnjë libër</h3>
            <p>Provo një kërkim tjetër ose ndrysho filtrat.</p>
          </div>
        )}
      </section>
      <Toast message={message} onClose={() => setMessage('')} />
      <Toast message={error} type="error" onClose={() => setError('')} />
    </>
  );
}
