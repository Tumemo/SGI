import { BrowserRouter, Route, Routes } from 'react-router-dom'
import Login from './pages/Login'
import './shared/tailwind.css'
import Home from './pages/Home'
import NovaEdicao from './pages/NovaEdicao'


function App() {

  return (
    <>
      <BrowserRouter>
        <Routes>
          <Route path='/' element={<Login />} />
          <Route path='/home' element={<Home />} /> 
          <Route path='/NovaEdicao' element={<NovaEdicao />} />
        </Routes> 
      </BrowserRouter>
    </>
  )
}

export default App
