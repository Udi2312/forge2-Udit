import { useState, useEffect } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { useAuth } from '../lib/auth'
import api from '../lib/api'
import {
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
  PieChart, Pie, Cell, LineChart, Line, Legend,
} from 'recharts'
import NotificationBell from '../components/NotificationBell'

const STATUS_COLORS = {
  open: '#3b82f6',
  pending: '#eab308',
  resolved: '#22c55e',
  closed: '#9ca3af',
}

const PRIORITY_COLORS = {
  low: '#9ca3af',
  medium: '#3b82f6',
  high: '#f97316',
  urgent: '#ef4444',
}

export default function Insights() {
  const navigate = useNavigate()
  const { user, logout } = useAuth()
  const [metrics, setMetrics] = useState(null)
  const [loading, setLoading] = useState(true)
  const [from, setFrom] = useState('')
  const [to, setTo] = useState('')

  const fetchMetrics = () => {
    const params = new URLSearchParams()
    if (from) params.set('from', from)
    if (to) params.set('to', to)
    setLoading(true)
    api.get(`/api/dashboard/metrics?${params}`)
      .then((res) => setMetrics(res.data))
      .finally(() => setLoading(false))
  }

  useEffect(() => { fetchMetrics() }, [])

  // Auto-poll every 30s
  useEffect(() => {
    const interval = setInterval(fetchMetrics, 30000)
    return () => clearInterval(interval)
  }, [from, to])

  const statusData = metrics
    ? Object.entries(metrics.by_status || {}).map(([name, value]) => ({ name, value }))
    : []

  const priorityData = metrics
    ? Object.entries(metrics.by_priority || {}).map(([name, value]) => ({ name, value }))
    : []

  const perDayData = metrics
    ? Object.entries(metrics.tickets_per_day || {}).map(([date, count]) => ({ date: date.slice(5), count }))
    : []

  const agentData = metrics?.agent_stats || []

  return (
    <div className="min-h-screen bg-gray-50">
      <nav className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 flex justify-between h-16 items-center">
          <div className="flex items-center gap-4">
            <Link to="/" className="text-indigo-600 text-sm">← Tickets</Link>
            <h1 className="text-xl font-bold text-gray-900">PulseDesk Insights</h1>
          </div>
          <div className="flex items-center gap-3">
            <NotificationBell />
            <button onClick={() => { logout(); navigate('/login') }} className="text-sm text-indigo-600">
              Sign out
            </button>
          </div>
        </div>
      </nav>

      <main className="max-w-7xl mx-auto px-4 py-8">
        {/* Filters */}
        <div className="flex items-end gap-3 mb-6">
          <div>
            <label className="block text-xs text-gray-500 mb-1">From</label>
            <input
              type="date"
              value={from}
              onChange={(e) => setFrom(e.target.value)}
              className="border rounded-md px-2 py-1 text-sm"
            />
          </div>
          <div>
            <label className="block text-xs text-gray-500 mb-1">To</label>
            <input
              type="date"
              value={to}
              onChange={(e) => setTo(e.target.value)}
              className="border rounded-md px-2 py-1 text-sm"
            />
          </div>
          <button
            onClick={fetchMetrics}
            className="px-4 py-1.5 bg-indigo-600 text-white rounded-md text-sm"
          >
            Apply
          </button>
          <span className="text-xs text-gray-400 ml-auto">Auto-refreshes every 30s</span>
        </div>

        {loading && !metrics ? (
          <div className="flex items-center justify-center h-64 text-gray-400">Loading metrics...</div>
        ) : metrics ? (
          <>
            {/* Metric cards */}
            <div className="grid grid-cols-5 gap-4 mb-6">
              <MetricCard label="Total" value={metrics.totals.all} color="text-gray-900" />
              <MetricCard label="Open" value={metrics.totals.open} color="text-blue-600" />
              <MetricCard label="Pending" value={metrics.totals.pending} color="text-yellow-600" />
              <MetricCard label="Resolved" value={metrics.totals.resolved} color="text-green-600" />
              <MetricCard label="Avg Resolution" value={`${metrics.avg_resolution_hours}h`} color="text-indigo-600" />
            </div>

            {/* Charts row */}
            <div className="grid grid-cols-2 gap-6 mb-6">
              {/* Status breakdown */}
              <div className="bg-white rounded-lg shadow p-6">
                <h3 className="font-semibold text-gray-900 mb-4">Tickets by Status</h3>
                <ResponsiveContainer width="100%" height={250}>
                  <PieChart>
                    <Pie
                      data={statusData}
                      dataKey="value"
                      nameKey="name"
                      cx="50%"
                      cy="50%"
                      outerRadius={80}
                      label={(entry) => `${entry.name}: ${entry.value}`}
                    >
                      {statusData.map((entry) => (
                        <Cell key={entry.name} fill={STATUS_COLORS[entry.name] || '#ccc'} />
                      ))}
                    </Pie>
                    <Tooltip />
                  </PieChart>
                </ResponsiveContainer>
              </div>

              {/* Priority breakdown */}
              <div className="bg-white rounded-lg shadow p-6">
                <h3 className="font-semibold text-gray-900 mb-4">Tickets by Priority</h3>
                <ResponsiveContainer width="100%" height={250}>
                  <BarChart data={priorityData}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis dataKey="name" />
                    <YAxis />
                    <Tooltip />
                    <Bar dataKey="value" radius={[4, 4, 0, 0]}>
                      {priorityData.map((entry) => (
                        <Cell key={entry.name} fill={PRIORITY_COLORS[entry.name] || '#ccc'} />
                      ))}
                    </Bar>
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </div>

            {/* Volume over time */}
            <div className="bg-white rounded-lg shadow p-6 mb-6">
              <h3 className="font-semibold text-gray-900 mb-4">Ticket Volume (Last 14 Days)</h3>
              <ResponsiveContainer width="100%" height={250}>
                <LineChart data={perDayData}>
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis dataKey="date" />
                  <YAxis />
                  <Tooltip />
                  <Line type="monotone" dataKey="count" stroke="#6366f1" strokeWidth={2} dot={{ r: 4 }} />
                </LineChart>
              </ResponsiveContainer>
            </div>

            {/* Agent performance */}
            <div className="bg-white rounded-lg shadow p-6">
              <h3 className="font-semibold text-gray-900 mb-4">Agent Performance</h3>
              {agentData.length > 0 ? (
                <div className="overflow-x-auto">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="border-b text-left text-gray-500">
                        <th className="pb-2 pr-4">Agent</th>
                        <th className="pb-2 pr-4">Email</th>
                        <th className="pb-2 pr-4 text-right">Total</th>
                        <th className="pb-2 pr-4 text-right">Open</th>
                        <th className="pb-2 pr-4 text-right">Resolved</th>
                      </tr>
                    </thead>
                    <tbody>
                      {agentData.map((a) => (
                        <tr key={a.id} className="border-b last:border-0">
                          <td className="py-2 pr-4 font-medium text-gray-900">{a.name}</td>
                          <td className="py-2 pr-4 text-gray-500">{a.email}</td>
                          <td className="py-2 pr-4 text-right">{a.assigned_total}</td>
                          <td className="py-2 pr-4 text-right">
                            <span className="inline-block px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">
                              {a.assigned_open}
                            </span>
                          </td>
                          <td className="py-2 pr-4 text-right">
                            <span className="inline-block px-2 py-0.5 rounded-full bg-green-100 text-green-700">
                              {a.assigned_resolved}
                            </span>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : (
                <p className="text-gray-400 text-sm">No agents found.</p>
              )}
            </div>
          </>
        ) : null}
      </main>
    </div>
  )
}

function MetricCard({ label, value, color }) {
  return (
    <div className="bg-white rounded-lg shadow p-4">
      <p className="text-xs text-gray-500 uppercase tracking-wide">{label}</p>
      <p className={`text-2xl font-bold mt-1 ${color}`}>{value}</p>
    </div>
  )
}
