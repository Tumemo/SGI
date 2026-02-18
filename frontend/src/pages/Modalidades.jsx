import BannerGlobal from "../components/BannerGlobal/BannerGlobal"
import NavBar from "../components/NavBar/NavBar"
import ButtonCard from '../components/ButtonCard/ButtonCard'
import TextTop from "../components/TextTop/TextTop"
import { useEffect } from "react"
import DadosJson from "../assets/json/dados_db.json"

function Modalidades(){
    useEffect(()=>{
        document.title = "SGM - Modalidades"
    },[])

    return(
        <>
            <BannerGlobal titulo="Modalidades"/>
            <TextTop textTop="Escolha uma modalidade para editar"/>

            {DadosJson.modalidades.map(modalidade =>
                <ButtonCard icon={modalidade.icone} titulo={modalidade.nome} invert={modalidade.invert}/>

            )}
            <NavBar />
        </>
    )
}

export default Modalidades