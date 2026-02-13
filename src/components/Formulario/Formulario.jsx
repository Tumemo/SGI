import { Link } from "react-router-dom"
import Input from "../Input/Input"

function Formulario() {
    return (
        <form action="#" className="flex flex-col items-center m-auto gap-4 mt-6">
            <Input tipo="email" placeholder="email" />
            <Input tipo="password" placeholder="senha"/>
            <p>Esqueci minha senha</p>
            <Link to="/home" className="bg-red-500 text-lg text-white w-[80%] font-bold text-center py-1 rounded-md">Entrar</Link>
        </form>

    )
}

export default Formulario