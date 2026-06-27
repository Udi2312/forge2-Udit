import { useState, useEffect } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import axios from 'axios'
import { useAuth } from '../auth'

export default function TicketDetail() {
  const { id } = useParams()
  const navigate = useNavigate()
  const { user } = useAuth()
  const [ticket, setTicket] = useState(null)
  const [loading, setLoading] = useState(true)
  const [comment, setComment] = useState('')
  const [isInternal, setIsInternal] = useState(false)
  const [posting, setPosting] = useState(false)

  useEffect(() => {
    axios.get(`/api/tickets/${id}`).then(res => {
      setTicket(res.data)
      setLoading(false)
    }).catch(() => {
      setLoading(false)
    })
  }, [id])

  const postComment = async (e) => {
    e.preventDefault()
    if (!comment.trim()) return
    setPosting(true)
    try {
      const res = await axios.post(`/api/tickets/${id}/comments`, {
        body: comment, is_internal: isInternal,
      })
      setTicket({ ...ticket, comments: [...(ticket.comments || []), res.data] })
      setComment('')
      setIsInternal(false)
    } finally {
      setPosting(false)
    }
  }

  const updateStatus = async (status) => {
    const res = await axios.put(`/api/tickets/${id}`, { status })
    setTicket(res.data)
  }

  if (loading) return <div className="min-h-screen flex items-center justify-center text-gray-400">Loading…</div>
  if (!ticket) return <div className="min-h-screen flex items-center justify-center text-gray-400">Ticket not found</div>

  return (
    <div className="min-h-screen bg-gray-50">
      <nav className="bg-white border-b border-gray-200">
        <div className="max-w-4xl mx-auto px-4 py-3 flex items-center gap-3">
          <button onClick={() => navigate('/')} className="text-sm text-gray-500 hover:text-gray-900">← Back</button>
          <span className="text-xl font-bold text-gray-900">PulseDesk</span>
        </div>
      </nav>

      <div className="max-w-4xl mx-auto px-4 py-6 space-y-6">
        {/* Ticket header */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div className="flex items-start justify-between mb-3">
            <h1 className="text-xl font-bold text-gray-900">{ticket.subject}</h1>
            <select value={ticket.status} onChange={e => updateStatus(e.target.value)}
              className="text-sm rounded-md border border-gray-300 px-2 py-1">
              <option value="open">Open</option>
              <option value="pending">Pending</option>
              <option value="resolved">Resolved</option>
              <option value="closed">Closed</option>
            </select>
          </div>
          <div className="flex items-center gap-4 text-sm text-gray-500 mb-4">
            <span>Priority: <strong className="text-gray-700 capitalize">{ticket.priority}</strong></span>
            <span>Requester: <strong className="text-gray-700">{ticket.requester?.name}</strong></span>
            {ticket.assignee && <span>Assignee: <strong className="text-gray-700">{ticket.assignee.name}</strong></span>}
          </div>
          <p className="text-gray-700 whitespace-pre-wrap">{ticket.description}</p>
        </div>

        {/* Comments */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 className="font-semibold text-gray-900 mb-4">Comments ({ticket.comments?.length || 0})</h3>
          <div className="space-y-3">
            {(ticket.comments || []).map(c => (
              <div key={c.id} className={`rounded-lg p-3 ${c.is_internal ? 'bg-amber-50 border border-amber-200' : 'bg-gray-50 border border-gray-200'}`}>
                <div className="flex items-center justify-between mb-1">
                  <span className="text-sm font-medium text-gray-700">{c.author?.name}</span>
                  {c.is_internal && <span className="text-xs px-2 py-0.5 rounded bg-amber-200 text-amber-800">Internal</span>}
                </div>
                <p className="text-sm text-gray-700">{c.body}</p>
              </div>
            ))}
          </div>

          {/* Add comment */}
          <form onSubmit={postComment} className="mt-4 space-y-2">
            <textarea value={comment} onChange={e => setComment(e.target.value)}
              placeholder="Write a reply…" rows="3"
              className="w-full rounded-md border border-gray-300 px-3 py-2" />
            <div className="flex items-center justify-between">
              {user?.role !== 'customer' && (
                <label className="flex items-center gap-2 text-sm text-gray-600">
                  <input type="checkbox" checked={isInternal} onChange={e => setIsInternal(e.target.checked)} />
                  Internal note
                </label>
              )}
              <button type="submit" disabled={posting || !comment.trim()}
                className="bg-indigo-600 text-white rounded-md px-4 py-2 text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 ml-auto">
                {posting ? 'Posting…' : 'Post'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  )
}
