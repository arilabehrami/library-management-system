import { useEffect, useState } from 'react';
import { api } from '../api.js';

export default function useData(path, refreshKey = 0) {
  const [state, setState] = useState({
    data: null,
    error: '',
    loading: true,
    path,
  });
  useEffect(() => {
    let active = true;
    api(path).then(
      (data) => {
        if (active) setState({ data, error: '', loading: false, path });
      },
      (err) => {
        if (active)
          setState({ data: null, error: err.message, loading: false, path });
      },
    );
    return () => {
      active = false;
    };
  }, [path, refreshKey]);
  return state.path === path ? state : { data: null, error: '', loading: true };
}
