import BannerGlobal from "../Componentes/BannerGlobal/BannerGlobal"
import NavBar from "../Componentes/NavBar/NavBar"
import CardInfo from '../Componentes/CardInfo/CardInfo'
import TextTop from '../Componentes/TextTop/TextTop'
import { useEffect } from "react"
import { Link } from "react-router-dom"

function Turmas(){
    useEffect(()=>{
        document.title = "SGM - Turmas"
    },[])

    return(
        <>
            <BannerGlobal titulo="Turmas" voltar="/Home"/>
            <TextTop texto="Editar detalhes das turmas"/>
            <main className="w-[80%] flex flex-col gap-4 m-auto mt-5 pb-20">
                <Link to={"/Alunos"}>
                    <CardInfo titulo="3• Ano EM"/>
                </Link>
                <CardInfo titulo="2• Ano EM"/>
                <CardInfo titulo="1• Ano EM"/>
            </main>
            <NavBar />
        </>
    )
}

export default Turmas