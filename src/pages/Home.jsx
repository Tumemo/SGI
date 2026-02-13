import { Link } from "react-router-dom"
import BannerGlobal from "../components/BannerGlobal/BannerGlobal"
import Botao from "../components/Botao/Botao"
import NavBar from "../components/NavBar/NavBar"
import ButtonCard from "../components/ButtonCard/ButtonCard"

function Home(){
    return(
        < >
        <BannerGlobal />
        <Link to={"/NovaEdicao"}>
            <Botao> <img src="/icons/plus-icon.svg" alt="Mais" /> Criar Nova edição</Botao>
        </Link>
        <ButtonCard texto="Interclasse 2026" status="Em andamento" icon="/icons/house-icon.svg"/>
        <NavBar />
        </>
    )
}

export default Home