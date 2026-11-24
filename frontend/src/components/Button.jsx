export default function Button({
  loading,
  disabled,
  children,
  className = '',
  type = 'button',
  ...props
}) {
  return (
    <button
      {...props}
      type={type}
      className={`btn ${className}`}
      disabled={loading || disabled}
      aria-busy={loading || undefined}
    >
      {loading ? 'Duke procesuar…' : children}
    </button>
  );
}
