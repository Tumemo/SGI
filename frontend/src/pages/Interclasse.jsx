import BannerGlobal from "../components/BannerGlobal/BannerGlobal"
import NavBar from "../components/NavBar/NavBar"
import ButtonCard from '../components/ButtonCard/ButtonCard'
import { Link } from "react-router-dom"
import { useEffect } from "react"
import TextTop from "../components/TextTop/TextTop"

function Interclasse(){
    useEffect(()=>{
        document.title = "SGM - Interclasse 2026"
    },[])

    return(
        <>
            <BannerGlobal titulo="Interclasse 2026"/>
            <TextTop textTop="Editar detalhes do interclasse"/>
            <Link to={"/Modalidades"} >
                <ButtonCard icon="/icons/trophy-icon.svg" texto="Modalidades" seta="true" invert="true"/>
            </Link>
            <Link to={"/Arrecadacao"}>
                <ButtonCard icon="/icons/cesta-icon.svg" texto="Arrecadação" seta="true" invert="false"/>
            </Link>
            <Link to={"/Regulamento"}>
                <ButtonCard icon="/icons/livro-regulamento-icon.svg" texto="Regulamento" seta="true" invert="false"/>
            </Link>
            <Link to={"/Turmas"}>
                <ButtonCard icon="/icons/person-icon.svg" texto="Turmas" seta="true" invert="false"/>
            </Link>
            <NavBar />
        </>
    )
}

export default Interclasse