import { Link } from "react-router-dom"
import BannerGlobal from "../components/BannerGlobal/BannerGlobal"
import ButtonCard from "../components/ButtonCard/ButtonCard"
import NavBar from "../components/NavBar/NavBar"
import { useEffect } from "react"


function Turmas(){

    useEffect(()=>{
        document.title = "SGM - Turmas"
    },[])
    return(
        <>
        <BannerGlobal titulo="Turmas"/>
        <section>
            <Link to={"/Alunos"}>
                <ButtonCard texto="3• Ano EM" seta="true"/>
            </Link>
            <ButtonCard texto="2• Ano EM" seta="true"/>
            <ButtonCard texto="1• Ano EM" seta="true"/>
        </section>
        <NavBar />
        </>
    )
}

export default Turmas