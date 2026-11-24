import { useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { api, setToken } from '../api.js';
import Button from '../components/Button.jsx';

export default function Login() {
  const navigate = useNavigate();
  const [params] = useSearchParams();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [visible, setVisible] = useState(false);
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);
  async function login(e) {
    e.preventDefault();
    setBusy(true);
    setError('');
    try {
      const data = await api('/auth/login', {
        method: 'POST',
        body: { email, password },
      });
      setToken(data.token);
      localStorage.setItem('lms_user', JSON.stringify(data.user));
      navigate('/');
    } catch (err) {
      setError(err.message);
    } finally {
      setBusy(false);
    }
  }
  return (
    <main className="login-layout">
      <section className="login-story">
        <a className="brand" href="/login">
          <span className="brand-mark">B</span> Biblioteka
        </a>
        <div>
          <span className="eyebrow">NJË VEND PËR IDE TË REJA</span>
          <h1>
            Libri yt i radhës
            <br />
            fillon këtu.
          </h1>
          <p>
            Zbulo libra, menaxho huazimet dhe gjej gjithmonë kohë për një
            histori të re.
          </p>
          <div className="book-art" aria-hidden="true">
            <span>IDE</span>
            <span>HISTORI</span>
            <span>DIJE</span>
            <span>ZBULIME</span>
          </div>
        </div>
        <p className="login-note">Katalogu · Huazimet · Biblioteka jote</p>
      </section>
      <section className="login-form">
        <span className="eyebrow">MIRË SE ERDHE PËRSËRI</span>
        <h2>Hyr në bibliotekë</h2>
        <p className="muted">
          Përdor llogarinë që të ka krijuar administratori.
        </p>
        {params.has('expired') && (
          <p className="notice">
            Sesioni ka skaduar. Hyr përsëri për të vazhduar.
          </p>
        )}
        <form onSubmit={login}>
          <label>
            Email
            <input
              className="input"
              type="email"
              autoComplete="username"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="emri@shembull.com"
              required
            />
          </label>
          <label>
            Fjalëkalimi
            <div className="password-field">
              <input
                className="input"
                type={visible ? 'text' : 'password'}
                autoComplete="current-password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
              />
              <button type="button" onClick={() => setVisible(!visible)}>
                {visible ? 'Fshih' : 'Shfaq'}
              </button>
            </div>
          </label>
          {error && (
            <p className="error-box" role="alert">
              {error}
            </p>
          )}
          <Button type="submit" loading={busy}>
            Hyr në llogari
          </Button>
        </form>
        <p className="muted small">
          Ke nevojë për një llogari? Kontakto administratorin e bibliotekës.
        </p>
      </section>
    </main>
  );
}
