import { useEffect } from "react"
import BannerGlobal from "../Componentes/BannerGlobal/BannerGlobal"
import CardAluno from "../Componentes/CardAluno/CardAluno"
import NavBar from "../Componentes/NavBar/NavBar"

function Alunos(){
    useEffect(()=>{
        document.title = "SGM - Alunos"
    },[])

    return(
        <>
        
            <BannerGlobal titulo="Alunos" voltar="/Turmas" />

            <CardAluno modalidade="Futsal" nome="Neymar" aberto="true"/>
            <CardAluno modalidade="Basquete" nome="Lebron"/>
            <NavBar />
        
        </>
    )
}

export default Alunos