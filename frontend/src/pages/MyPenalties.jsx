import { useCallback, useEffect, useState } from 'react';
import { api } from '../api.js';
import Toast from '../components/Toast.jsx';
import Button from '../components/Button.jsx';

export default function MyPenalties({ onChange, refreshKey }) {
  const [penalties, setPenalties] = useState([]);
  const [busyId, setBusyId] = useState(null);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await api('/me/penalties');
      setPenalties(data.penalties);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    Promise.resolve().then(load);
  }, [load, refreshKey]);

  async function pay(loanId) {
    const penalty = penalties.find((p) => p.loan_id === loanId);
    if (
      !window.confirm(
        `Paguaj €${penalty.amount}? Pagesa simulohet për këtë demonstrim.`,
      )
    )
      return;

    setBusyId(loanId);
    setError('');
    try {
      await api(`/loans/${loanId}/penalty/pay`, { method: 'POST' });
      setMessage('Penaliteti u pagua.');
      await load();
      onChange?.();
    } catch (err) {
      setError(err.message);
    } finally {
      setBusyId(null);
    }
  }

  return (
    <div className="card">
      <h2>Penalitetet e mia</h2>
      {loading && (
        <p className="muted" role="status">
          Duke ngarkuar…
        </p>
      )}
      <p className="muted">
        Shumat përfundimtare pas kthimit të librave. Pagesat online janë të
        simuluara.
      </p>

      <table>
        <thead>
          <tr>
            <th>Huazimi</th>
            <th>Ditë vonese</th>
            <th>Shuma (€)</th>
            <th>Statusi</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          {penalties.map((p) => (
            <tr key={p.id}>
              <td>#{p.loan_id}</td>
              <td>{p.days_late}</td>
              <td>{p.amount}</td>
              <td>
                <span className={`badge ${p.paid ? 'badge-ok' : 'badge-bad'}`}>
                  {p.paid ? 'E paguar' : 'E papaguar'}
                </span>
              </td>
              <td>
                {!p.paid && (
                  <Button
                    loading={busyId === p.loan_id}
                    onClick={() => pay(p.loan_id)}
                  >
                    Paguaj
                  </Button>
                )}
              </td>
            </tr>
          ))}
          {penalties.length === 0 && (
            <tr>
              <td colSpan="5">Asnjë penalitet.</td>
            </tr>
          )}
        </tbody>
      </table>

      <Toast message={message} type="ok" onClose={() => setMessage('')} />
      <Toast message={error} type="error" onClose={() => setError('')} />
    </div>
  );
}
