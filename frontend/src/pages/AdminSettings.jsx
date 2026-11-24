import { useCallback, useEffect, useState } from 'react';
import { api } from '../api.js';
import Toast from '../components/Toast.jsx';
import Button from '../components/Button.jsx';

const LABELS = {
  borrow_fee: 'Tarifa e huazimit (€)',
  penalty_rate: 'Penaliteti për ditë (€)',
  borrowing_period: 'Periudha e huazimit (ditë)',
  borrowing_limit: 'Limiti i huazimeve',
};

export default function AdminSettings() {
  const [settings, setSettings] = useState({});
  const [drafts, setDrafts] = useState({});
  const [busyKey, setBusyKey] = useState(null);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await api('/admin/settings');
      setSettings(data.settings);
      setDrafts(data.settings);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    Promise.resolve().then(load);
  }, [load]);

  async function save(key) {
    setBusyKey(key);
    setError('');
    try {
      await api(`/admin/settings/${key}`, {
        method: 'PUT',
        body: { value: drafts[key] },
      });
      setMessage('Cilësimi u ruajt.');
      await load();
    } catch (err) {
      setError(err.message);
    } finally {
      setBusyKey(null);
    }
  }

  return (
    <div className="card">
      <h2>Cilësimet</h2>
      {loading && (
        <p className="muted" role="status">
          Duke ngarkuar…
        </p>
      )}
      <table>
        <tbody>
          {Object.keys(LABELS).map((key) => (
            <tr key={key}>
              <td>{LABELS[key]}</td>
              <td>
                <input
                  aria-label={LABELS[key]}
                  className="input"
                  value={drafts[key] ?? ''}
                  onChange={(e) =>
                    setDrafts({ ...drafts, [key]: e.target.value })
                  }
                />
              </td>
              <td>
                <Button
                  loading={busyKey === key}
                  disabled={drafts[key] === settings[key]}
                  onClick={() => save(key)}
                >
                  Ruaj
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
