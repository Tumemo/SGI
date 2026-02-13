import { Link } from "react-router-dom"
import BannerGlobal from "../components/BannerGlobal/BannerGlobal"
import Botao from "../components/Botao/Botao"
import NavBar from "../components/NavBar/NavBar"

function Home(){
    return(
        <>
        <BannerGlobal />
        <Link to={"/NovaEdicao"}>
            <Botao> <img src="/icons/plus-icon.svg" alt="Mais" /> Criar Nova edição</Botao>
        </Link>
        <NavBar />
        </>
    )
}

export default Home