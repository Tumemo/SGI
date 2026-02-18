import { BrowserRouter, Route, Routes } from 'react-router-dom'
import Login from './pages/Login'
import './shared/tailwind.css'
import Home from './pages/Home'
import NovaEdicao from './pages/NovaEdicao'
import Turmas from './pages/Turmas'
import Alunos from './pages/Alunos'
import Interclasse from './pages/Interclasse'
import Modalidades from './pages/Modalidades'
import Arrecadacao from './pages/Arrecadacao'
import Regulamento from './pages/Regulamento'
import Ranking from './pages/Ranking'


function App() {

  return (
    <>
      <BrowserRouter>
        <Routes>
          <Route path='/' element={<Login />} />
          <Route path='/Home' element={<Home />} /> 
          <Route path='/NovaEdicao' element={<NovaEdicao />} />
          <Route path='/Turmas' element={<Turmas />} />
          <Route path='/Alunos' element={<Alunos />} />
          <Route path='/Interclasse' element={<Interclasse />} />
          <Route path='/Modalidades' element={<Modalidades />} />
          <Route path='/Arrecadacao' element={<Arrecadacao />}/>
          <Route path='/Regulamento' element={<Regulamento />}/>
          <Route path='/Ranking' element={<Ranking />}/>
        </Routes> 
      </BrowserRouter>
    </>
  )
}

export default App
