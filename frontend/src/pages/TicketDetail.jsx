import { useState, useEffect } from 'react'
import { useParams, useNavigate, Link } from 'react-router-dom'
import { useAuth } from '../lib/auth'
import api from '../lib/api'

export default function TicketDetail() {
  const { id } = useParams()
  const navigate = useNavigate()
  const { user, logout } = useAuth()
  const [ticket, setTicket] = useState(null)
  const [comments, setComments] = useState([])
  const [newComment, setNewComment] = useState('')
  const [isInternal, setIsInternal] = useState(false)

  useEffect(() => {
    Promise.all([
      api.get(`/api/tickets/${id}`),
      api.get(`/api/tickets/${id}/comments`),
    ]).then(([t, c]) => {
      setTicket(t.data)
      setComments(c.data)
    })
  }, [id])

  const handleAddComment = async (e) => {
    e.preventDefault()
    if (!newComment.trim()) return
    const res = await api.post(`/api/tickets/${id}/comments`, {
      body: newComment,
      is_internal: isInternal,
    })
    setComments([...comments, res.data])
    setNewComment('')
    setIsInternal(false)
  }

  const handleStatusChange = async (status) => {
    const res = await api.put(`/api/tickets/${id}`, { status })
    setTicket(res.data)
  }

  const statusColors = {
    open: 'bg-blue-100 text-blue-800',
    pending: 'bg-yellow-100 text-yellow-800',
    resolved: 'bg-green-100 text-green-800',
    closed: 'bg-gray-100 text-gray-800',
  }

  if (!ticket) return <div className="min-h-screen flex items-center justify-center">Loading...</div>

  return (
    <div className="min-h-screen bg-gray-50">
      <nav className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 flex justify-between h-16 items-center">
          <div className="flex items-center gap-4">
            <Link to="/" className="text-indigo-600 text-sm">← Back</Link>
            <h1 className="text-xl font-bold text-gray-900">PulseDesk</h1>
          </div>
          <button onClick={() => { logout(); navigate('/login') }} className="text-sm text-indigo-600">
            Sign out
          </button>
        </div>
      </nav>

      <main className="max-w-4xl mx-auto px-4 py-8">
        <div className="bg-white rounded-lg shadow p-6 mb-6">
          <div className="flex justify-between items-start mb-4">
            <div>
              <h2 className="text-2xl font-bold text-gray-900">{ticket.subject}</h2>
              <p className="text-sm text-gray-500 mt-1">#{ticket.id} · by {ticket.requester?.name}</p>
            </div>
            <span className={`px-3 py-1 rounded-full text-sm font-medium ${statusColors[ticket.status]}`}>
              {ticket.status}
            </span>
          </div>
          <p className="text-gray-700 whitespace-pre-wrap">{ticket.description}</p>

          <div className="mt-4 flex gap-2">
            {['open', 'pending', 'resolved', 'closed'].map((s) => (
              <button
                key={s}
                onClick={() => handleStatusChange(s)}
                className={`px-3 py-1 rounded-md text-xs font-medium border ${
                  ticket.status === s ? 'bg-indigo-600 text-white border-indigo-600' : 'text-gray-600'
                }`}
              >
                {s}
              </button>
            ))}
          </div>
        </div>

        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="font-semibold text-gray-900 mb-4">Comments ({comments.length})</h3>
          <div className="space-y-4 mb-6">
            {comments.map((c) => (
              <div key={c.id} className={`p-3 rounded-lg ${c.is_internal ? 'bg-yellow-50 border border-yellow-200' : 'bg-gray-50'}`}>
                <div className="flex justify-between items-center mb-1">
                  <span className="text-sm font-medium text-gray-900">{c.author?.name}</span>
                  {c.is_internal && (
                    <span className="text-xs bg-yellow-200 text-yellow-800 px-2 py-0.5 rounded">Internal</span>
                  )}
                </div>
                <p className="text-sm text-gray-700 whitespace-pre-wrap">{c.body}</p>
              </div>
            ))}
            {comments.length === 0 && <p className="text-gray-400 text-sm">No comments yet.</p>}
          </div>

          <form onSubmit={handleAddComment}>
            <textarea
              value={newComment}
              onChange={(e) => setNewComment(e.target.value)}
              placeholder="Write a comment..."
              className="block w-full rounded-md border-gray-300 border p-2 mb-2"
              rows={3}
            />
            <div className="flex items-center gap-4">
              <label className="flex items-center gap-2 text-sm text-gray-600">
                <input
                  type="checkbox"
                  checked={isInternal}
                  onChange={(e) => setIsInternal(e.target.checked)}
                  className="rounded"
                />
                Internal note
              </label>
              <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm">
                Add comment
              </button>
            </div>
          </form>
        </div>
      </main>
    </div>
  )
}
