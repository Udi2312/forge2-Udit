import { BrowserRouter, Routes, Route } from 'react-router-dom'
import axios from 'axios'

axios.defaults.baseURL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000'
axios.defaults.withCredentials = true

function Home() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50">
      <div className="text-center">
        <h1 className="text-4xl font-bold text-gray-900 mb-4">
          PulseDesk
        </h1>
        <p className="text-gray-600">
          Support desk platform — frontend scaffold ready.
        </p>
      </div>
    </div>
  )
}

function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<Home />} />
      </Routes>
    </BrowserRouter>
  )
}

export default App
