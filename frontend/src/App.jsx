import Home from './pages/Home'
import Login from './pages/Login'
import Turmas from './pages/Turmas'
import Ranking from './pages/Ranking'
import './shared/tailwind.css'
import {BrowserRouter, Routes, Route} from 'react-router-dom'
import NovaEdicao from './pages/NovaEdicao'
import Edicao from './pages/Edicao'

function App() {

  return (
    <>
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<Login />}/>
        <Route path="/Home" element={<Home />}/>
        <Route path='/NovaEdicao' element={<NovaEdicao />}/>
        <Route path='/Edicao/:id' element={<Edicao />} />
        <Route path='/Turmas' element={<Turmas />}/>
        <Route path='/Ranking/:id' element={<Ranking />}/>
      </Routes>
    </BrowserRouter>
     
    </>
  )
}

export default App
