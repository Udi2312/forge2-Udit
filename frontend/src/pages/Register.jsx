import { useState } from 'react'
import { useAuth } from '../auth'

export default function Register({ onSwitch }) {
  const { register } = useAuth()
  const [form, setForm] = useState({ name: '', email: '', password: '', organization_name: '' })
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value })

  const handleSubmit = async (e) => {
    e.preventDefault()
    setBusy(true)
    setError('')
    try {
      await register(form.name, form.email, form.password, form.organization_name)
    } catch (err) {
      setError(err.response?.data?.message || 'Registration failed')
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50">
      <div className="w-full max-w-md">
        <div className="text-center mb-8">
          <h1 className="text-3xl font-bold text-gray-900">PulseDesk</h1>
          <p className="text-gray-500 mt-1">Create your workspace</p>
        </div>
        <form onSubmit={handleSubmit} className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
          {error && <div className="text-sm text-red-600 bg-red-50 rounded p-3">{error}</div>}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Organization name</label>
            <input name="organization_name" value={form.organization_name} onChange={handleChange}
              className="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500"
              required />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Your name</label>
            <input name="name" value={form.name} onChange={handleChange}
              className="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500"
              required />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" name="email" value={form.email} onChange={handleChange}
              className="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500"
              required />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input type="password" name="password" value={form.password} onChange={handleChange}
              className="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500"
              required minLength="8" />
          </div>
          <button type="submit" disabled={busy}
            className="w-full bg-indigo-600 text-white rounded-md py-2 font-medium hover:bg-indigo-700 disabled:opacity-50">
            {busy ? 'Creating…' : 'Create workspace'}
          </button>
        </form>
        <p className="text-center text-sm text-gray-500 mt-4">
          Already have an account?{' '}
          <button onClick={onSwitch} className="text-indigo-600 hover:underline font-medium">Sign in</button>
        </p>
      </div>
    </div>
  )
}
