import BannerGlobal from "../components/BannerGlobal/BannerGlobal"
import NavBar from "../components/NavBar/NavBar"
import ButtonCard from '../components/ButtonCard/ButtonCard'
import { useEffect } from "react"
import DadosJson from "../assets/json/dados_db.json"

function Alunos(){
    useEffect(()=>{
        document.title = "SGM - Alunos"
    },[])

    return(
        <>
        <BannerGlobal titulo="Alunos"/>
        <ButtonCard estrutura="complexo" nome="Gustavo" modalidade="Basquete"  />
        <ButtonCard estrutura="complexo" nome="Gustavo" modalidade="Basquete" tipoComplexo="pontos"  />
        <NavBar />
        </>
    )
}

export default Alunos