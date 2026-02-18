import { Link } from "react-router-dom"
import BannerGlobal from "../components/BannerGlobal/BannerGlobal"
import Botao from "../components/Botao/Botao"
import NavBar from "../components/NavBar/NavBar"
import ButtonCard from "../components/ButtonCard/ButtonCard"
import { useEffect } from "react"

function Home() {

    useEffect(()=>{
        document.title = "SGM - Home"
    },[])
    return (
        < >
            <BannerGlobal />
            <Link to={"/NovaEdicao"}>
                <Botao> <img src="/icons/plus-icon.svg" alt="Mais" /> Criar Nova edição</Botao>
            </Link>
            <section className="w-full py-10 flex flex-col justify-center a">
                <Link to={"/Interclasse"}>
                    <ButtonCard texto="Interclasse 2026" status="Em andamento"/>
                </Link>
                <ButtonCard texto="Interclasse 2025" status="Finalizado" />
                <ButtonCard texto="Interclasse 2024" status="Finalizado" />
                <ButtonCard texto="Interclasse - Volta as Aulas" status="Finalizado" />
            </section>
            <NavBar />
        </>
    )
}

export default Home